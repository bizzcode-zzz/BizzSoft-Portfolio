<?php

namespace Tests\Feature;

use App\Enums\CustomizationRequestStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\CustomizationRequest;
use App\Models\PaymentAdjustment;
use App\Models\ProductOwnership;
use App\Payments\PaymentEntitlements;
use App\Payments\Providers\Paddle\PaddleAdjustmentProcessor;
use App\Payments\Providers\Paddle\PaddleWebhookProcessor;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\Support\AdjustmentTestCase;

class PaymentAdjustmentsTest extends AdjustmentTestCase
{
    public function test_approved_full_refund_preserves_history_and_holds_access(): void
    {
        $before = $this->license->fresh()->toArray();
        $ledger = $this->process();
        $this->assertTrue($ledger->hold_active);
        $this->assertFalse($ledger->review_required);
        $this->assertSame('automatic_hold', $ledger->decision);
        $this->assertSame($this->order->id, $ledger->order_id);
        $this->assertSame(PaymentStatus::Verified, $this->payment->fresh()->status);
        $this->assertSame(OrderStatus::Completed, $this->order->fresh()->status);
        $this->assertSame($before, $this->license->fresh()->toArray());
        $this->assertSame(1, ProductOwnership::count());
        $this->assertCount(1, $ledger->history);
    }

    public function test_tax_exempt_refund_uses_same_gross_contract(): void
    {
        $ledger = $this->process([
            'totals.subtotal' => '10000', 'totals.tax' => '0',
            'items.0.totals.subtotal' => '10000', 'items.0.totals.tax' => '0',
        ]);
        $this->assertTrue($ledger->hold_active);
    }

    #[DataProvider('inactiveRefunds')]
    public function test_pending_and_rejected_refunds_do_not_suspend(string $status): void
    {
        $ledger = $this->process(['status' => $status]);
        $this->assertFalse($ledger->hold_active);
        $this->assertSame('no_effect', $ledger->decision);
    }

    public static function inactiveRefunds(): array
    {
        return [['pending_approval'], ['rejected']];
    }

    #[DataProvider('ambiguousAdjustments')]
    public function test_partial_tax_and_ambiguous_totals_require_review(array $changes): void
    {
        $ledger = $this->process($changes);
        $this->assertFalse($ledger->hold_active);
        $this->assertTrue($ledger->review_required);
        $this->assertSame('partial_tax_or_ambiguous', $ledger->decision);
    }

    public static function ambiguousAdjustments(): array
    {
        return [
            [['type' => 'partial']], [['items.0.type' => 'tax', 'totals.subtotal' => '0']],
            [['totals.total' => '9999']], [['totals.total' => '10001']], [['totals.total' => '-10000']],
            [['totals.total' => '1e4']], [['totals.tax' => '0']], [['items' => []]],
            [['items.0.totals.total' => '9999']], [['type' => null]],
        ];
    }

    #[DataProvider('chargebacks')]
    public function test_chargeback_holds_are_reversible(string $action): void
    {
        $ledger = $this->process(['action' => $action]);
        $this->assertTrue($ledger->hold_active);
        $this->decide($ledger, 'restore')->assertRedirect();
        $this->assertFalse($ledger->fresh()->hold_active);
        $this->assertSame(PaymentStatus::Verified, $this->payment->fresh()->status);
    }

    public static function chargebacks(): array
    {
        return [['chargeback'], ['chargeback_warning']];
    }

    #[DataProvider('reversals')]
    public function test_reversals_never_automatically_restore(string $action): void
    {
        $original = $this->process(['action' => 'chargeback']);
        $reversal = $this->process(['id' => 'adj_reverse', 'action' => $action], 'evt_reverse');
        $this->assertSame('reversal_review', $reversal->decision);
        $this->assertTrue($reversal->review_required);
        $this->assertFalse($reversal->hold_active);
        $this->assertTrue($original->fresh()->hold_active);
    }

    public static function reversals(): array
    {
        return [['chargeback_reverse'], ['chargeback_warning_reverse'], ['credit_reverse']];
    }

    public function test_reversed_status_retains_existing_hold(): void
    {
        $ledger = $this->process();
        $this->process(['status' => 'reversed', 'updated_at' => '2026-09-30T03:00:00Z'], 'evt_reversed', true);
        $this->assertTrue($ledger->fresh()->hold_active);
        $this->assertTrue($ledger->fresh()->review_required);
    }

    public function test_duplicate_created_updated_replayed_and_stale_events_are_idempotent(): void
    {
        $ledger = $this->process();
        $this->assertFalse(app(PaddleWebhookProcessor::class)->process($this->event()));
        $this->process([], 'evt_same_state', true);
        $this->process(['status' => 'pending_approval', 'updated_at' => '2026-09-30T01:00:00.050000Z'], 'evt_old', true);
        $ledger->refresh();
        $this->assertSame(1, PaymentAdjustment::count());
        $this->assertCount(3, $ledger->history);
        $this->assertTrue($ledger->hold_active);
        $this->assertSame('approved', $ledger->snapshot['status']);
        $this->assertSame('100000', $ledger->provider_updated_at->format('u'));
    }

    public function test_conflicts_advance_tokens_and_monotonic_watermark(): void
    {
        $stale = $this->process();
        $ledger = $this->process(['customer_id' => 'ctm_conflict', 'updated_at' => '2026-09-30T01:00:00.300000Z'], 'evt_conflict', true);
        $this->assertSame('300000', $ledger->provider_updated_at->format('u'));
        $this->assertSame('evt_conflict', $ledger->last_event_id);
        $this->assertSame('ctm_trusted', $ledger->snapshot['customer_id']);
        $this->decide($stale, 'restore')->assertSessionHasErrors('reason');
        $ledger = $this->process(['customer_id' => 'ctm_conflict', 'updated_at' => '2026-09-30T01:00:00.200000Z'], 'evt_old_conflict', true);
        $this->assertSame('300000', $ledger->provider_updated_at->format('u'));
        $this->assertSame('evt_old_conflict', $ledger->last_event_id);
        $this->assertTrue($ledger->hold_active);
        $this->decide($ledger, 'review')->assertRedirect();
        $ledger = $this->process(['updated_at' => '2026-09-30T04:00:00Z', 'reason' => 'New evidence'], 'evt_later', true);
        $this->assertTrue($ledger->review_required);
        $this->assertSame('conflicting_event', $ledger->decision);
    }

    public function test_equal_version_conflict_is_review_only(): void
    {
        $this->process(['status' => 'pending_approval']);
        $ledger = $this->process([], 'evt_equal_conflict', true);
        $this->assertFalse($ledger->hold_active);
        $this->assertSame('conflicting_event', $ledger->decision);
    }

    public function test_review_note_does_not_disable_later_automatic_processing(): void
    {
        $ledger = $this->process(['status' => 'pending_approval']);
        $this->decide($ledger, 'review')->assertRedirect();
        $ledger = $this->process(['updated_at' => '2026-09-30T03:00:00Z'], 'evt_approved', true);
        $this->assertTrue($ledger->hold_active);
        $this->assertSame('automatic_hold', $ledger->decision);
    }

    public function test_manual_restore_cannot_be_silently_overridden(): void
    {
        $ledger = $this->process();
        $this->decide($ledger, 'restore')->assertRedirect();
        $ledger = $this->process(['reason' => 'New details', 'updated_at' => '2026-09-30T03:00:00Z'], 'evt_later', true);
        $this->assertFalse($ledger->hold_active);
        $this->assertTrue($ledger->review_required);
        $this->assertSame('provider_update_after_review', $ledger->decision);
    }

    public function test_manual_partial_refund_suspension_requires_explicit_restoration(): void
    {
        $ledger = $this->process(['type' => 'partial']);
        $this->decide($ledger, 'suspend')->assertRedirect();
        $ledger = $this->process(['type' => 'partial', 'status' => 'rejected', 'updated_at' => '2026-09-30T03:00:00Z'], 'evt_later', true);
        $this->assertTrue($ledger->hold_active);
        $this->assertTrue($ledger->review_required);
        $this->assertSame('provider_update_after_review', $ledger->decision);
    }

    public function test_distinct_identical_adjustments_count_separately_and_only_one_hold_is_restored(): void
    {
        $first = $this->process();
        $second = $this->process(['id' => 'adj_second'], 'evt_second');
        $this->assertSame($first->snapshot, $second->snapshot);
        $this->assertSame('cumulative_review', $second->decision);
        $this->assertFalse($second->hold_active);
        $this->decide($second, 'suspend')->assertRedirect();
        $this->decide($first, 'restore')->assertRedirect();
        $this->assertTrue(app(PaymentEntitlements::class)->orderIsHeld($this->order->id));
        $this->assertFalse($first->fresh()->hold_active);
        $this->assertTrue($second->fresh()->hold_active);
    }

    public function test_partial_refunds_are_not_summed_into_an_automatic_hold(): void
    {
        $partial = ['type' => 'partial', 'totals.subtotal' => '4000', 'totals.tax' => '1000', 'totals.total' => '5000'];
        $this->process($partial);
        $second = $this->process(['id' => 'adj_second', ...$partial], 'evt_second');
        $this->assertTrue($second->review_required);
        $this->assertFalse(app(PaymentEntitlements::class)->orderIsHeld($this->order->id));
    }

    #[DataProvider('invalidAttribution')]
    public function test_invalid_attribution_cannot_be_manually_overridden(string $field, string $value): void
    {
        $ledger = $this->process([$field => $value]);
        $this->assertFalse($ledger->hold_active);
        $this->assertTrue($ledger->review_required);
        $this->assertNull($ledger->order_id);
        $this->decide($ledger, 'suspend')->assertSessionHasErrors('reason');
    }

    public static function invalidAttribution(): array
    {
        return [['transaction_id', 'txn_unknown'], ['customer_id', 'ctm_wrong'], ['currency_code', 'EUR'], ['totals.currency_code', 'EUR']];
    }

    #[DataProvider('missingIdentity')]
    public function test_untrusted_historical_identity_remains_review_only(string $field, mixed $value): void
    {
        $metadata = $this->payment->metadata;
        data_set($metadata, $field, $value);
        $this->payment->update(['metadata' => $metadata]);
        $ledger = $this->process();
        $this->assertFalse($ledger->hold_active);
        $this->assertTrue($ledger->review_required);
        $this->decide($ledger, 'suspend')->assertSessionHasErrors('reason');
    }

    public static function missingIdentity(): array
    {
        return [
            ['paddle_customer_id', null], ['paddle_customer_identity_provenance', []],
            ['paddle_customer_identity_review_required', true],
            ['paddle_customer_identity_provenance.transaction_id', 'txn_wrong'],
            ['paddle_customer_identity_provenance.event_id', 'evt_missing'],
        ];
    }

    public function test_duplicate_provider_mapping_and_multiple_verified_payments_are_review_only(): void
    {
        $duplicate = $this->payment->replicate();
        $duplicate->payment_number = 'PAY-DUPLICATE';
        $duplicate->save();
        $ledger = $this->process();
        $this->assertFalse($ledger->hold_active);
        $this->assertNull($ledger->order_id);
        $duplicate->update(['provider_payment_id' => 'txn_other']);
        $ledger = $this->process(['id' => 'adj_other'], 'evt_other');
        $this->assertFalse($ledger->hold_active);
        $this->assertNull($ledger->order_id);
    }

    public function test_trusted_customer_identity_provenance_null_and_conflict_behavior(): void
    {
        $metadata = $this->payment->fresh()->metadata;
        $this->assertSame('ctm_trusted', $metadata['paddle_customer_id']);
        $this->assertSame($this->payment->provider_payment_id, $metadata['paddle_customer_identity_provenance']['transaction_id']);
        app(PaddleWebhookProcessor::class)->process($this->completed($this->payment, ['customer_id' => null], 'evt_null'));
        $this->assertSame('ctm_trusted', $this->payment->fresh()->metadata['paddle_customer_id']);
        app(PaddleWebhookProcessor::class)->process($this->completed($this->payment, ['customer_id' => 'ctm_other'], 'evt_conflict'));
        $this->assertSame('ctm_trusted', $this->payment->fresh()->metadata['paddle_customer_id']);
        $this->assertTrue($this->payment->fresh()->metadata['paddle_customer_identity_review_required']);
        $this->assertSame(PaymentStatus::Verified, $this->payment->fresh()->status);
        $this->assertFalse($this->process()->hold_active);
    }

    public function test_missing_customer_does_not_invalidate_payment_and_invalid_payment_cannot_write_identity(): void
    {
        $this->payment->update(['metadata' => [], 'status' => PaymentStatus::Pending]);
        app(PaddleWebhookProcessor::class)->process($this->completed($this->payment, ['customer_id' => null], 'evt_null'));
        $this->assertSame(PaymentStatus::Verified, $this->payment->fresh()->status);
        $this->assertArrayNotHasKey('paddle_customer_id', $this->payment->fresh()->metadata);
        try {
            app(PaddleWebhookProcessor::class)->process($this->completed($this->payment, ['details.totals.total' => '9999'], 'evt_invalid'));
            $this->fail('Invalid amount accepted');
        } catch (\InvalidArgumentException) {
            $this->assertArrayNotHasKey('paddle_customer_id', $this->payment->fresh()->metadata);
        }
    }

    public function test_customization_adjustment_hold_blocks_progress_and_can_be_restored(): void
    {
        $customization = CustomizationRequest::create([
            'user_id' => $this->customer->id,
            'title' => 'Refund protected customization',
            'description' => 'Custom work protected by payment holds.',
            'status' => CustomizationRequestStatus::Accepted,
        ]);

        $quote = $customization->quote()->create([
            'price' => '100.00',
            'scope' => 'Protected customization scope.',
            'estimated_delivery' => now()->addWeek()->toDateString(),
        ]);

        $payment = $quote->payments()->create([
            'payment_number' => 'PAY-CUSTOM-ADJUST-001',
            'user_id' => $this->customer->id,
            'amount' => '100.00',
            'currency' => 'USD',
            'provider' => 'paddle',
            'provider_payment_id' => 'txn_custom_adjust_001',
            'status' => PaymentStatus::Pending,
        ]);

        app(PaddleWebhookProcessor::class)->process(
            $this->completed(
                $payment,
                [],
                'evt_customization_completed'
            )
        );

        $this->payment = $payment->fresh();

        $ledger = $this->process(
            ['id' => 'adj_customization_full'],
            'evt_customization_refund'
        );

        $this->assertTrue($ledger->hold_active);
        $this->assertFalse($ledger->review_required);
        $this->assertSame('automatic_hold', $ledger->decision);
        $this->assertSame($payment->id, $ledger->payment_id);
        $this->assertNull($ledger->order_id);
        $this->assertSame(
            PaymentStatus::Verified,
            $payment->fresh()->status
        );

        $this->actingAs($this->admin)
            ->patch(
                route(
                    'admin.customizations.start-development',
                    $customization
                )
            )
            ->assertStatus(422);

        $customization->update([
            'status' => CustomizationRequestStatus::InProgress,
        ]);

        $this->actingAs($this->admin)
            ->patch(
                route(
                    'admin.customizations.ready-for-review',
                    $customization
                )
            )
            ->assertStatus(422);

        $customization->update([
            'status' => CustomizationRequestStatus::RevisionRequested,
        ]);

        $this->actingAs($this->admin)
            ->patch(
                route(
                    'admin.customizations.resume-development',
                    $customization
                )
            )
            ->assertStatus(422);

        $customization->update([
            'status' => CustomizationRequestStatus::ReadyForReview,
        ]);

        $this->actingAs($this->customer)
            ->patch(
                route(
                    'customizations.approve',
                    $customization
                )
            )
            ->assertStatus(422);

        $this->decide($ledger, 'restore')
            ->assertRedirect();

        $this->assertFalse(
            $ledger->fresh()->hold_active
        );

        $customization->update([
            'status' => CustomizationRequestStatus::Accepted,
        ]);

        $this->actingAs($this->admin)
            ->patch(
                route(
                    'admin.customizations.start-development',
                    $customization
                )
            )
            ->assertRedirect(
                route(
                    'admin.customizations.show',
                    $customization
                )
            );

        $this->assertSame(
            CustomizationRequestStatus::InProgress,
            $customization->fresh()->status
        );

        $partial = $this->process(
            [
                'id' => 'adj_customization_partial',
                'type' => 'partial',
            ],
            'evt_customization_partial'
        );

        $this->assertSame(
            'partial_tax_or_ambiguous',
            $partial->decision
        );

        $this->assertFalse($partial->hold_active);
        $this->assertTrue($partial->review_required);

        $this->decide($partial, 'suspend')
            ->assertRedirect();

        $this->assertTrue(
            $partial->fresh()->hold_active
        );

        $this->assertNull(
            $partial->fresh()->order_id
        );

        $this->assertSame(
            $payment->id,
            $partial->fresh()->payment_id
        );
    }

    public function test_admin_decision_rejects_malformed_stored_snapshot_without_mutating_hold(): void
    {
        $ledger = $this->process();

        $this->assertTrue($ledger->hold_active);

        $originalHistory = $ledger->history;
        $originalDecision = $ledger->decision;

        $ledger->update([
            'snapshot' => [],
        ]);

        $this->decide($ledger->fresh(), 'restore')
            ->assertSessionHasErrors('reason');

        $ledger->refresh();

        $this->assertTrue($ledger->hold_active);
        $this->assertSame($originalDecision, $ledger->decision);
        $this->assertSame($originalHistory, $ledger->history);
        $this->assertSame([], $ledger->snapshot);
    }
    public function test_signature_boundary_and_adjustment_dispatch(): void
    {
        $payload = json_encode([
            'event_id' => 'evt_http', 'event_type' => 'adjustment.created',
            'occurred_at' => '2026-09-30T02:00:00Z', 'data' => $this->adjustmentData(),
        ], JSON_THROW_ON_ERROR);
        $this->call('POST', route('webhooks.paddle'), [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload)->assertStatus(400);
        $this->assertSame(0, PaymentAdjustment::count());
        $time = (string) time();
        $signature = 'ts='.$time.';h1='.hash_hmac('sha256', $time.':'.$payload, 'offline-signature-secret');
        $this->call('POST', route('webhooks.paddle'), [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_PADDLE_SIGNATURE' => $signature,
        ], $payload)->assertOk()->assertJsonPath('processed', true);
        $this->assertTrue(PaymentAdjustment::firstOrFail()->hold_active);
    }

    #[DataProvider('moneyFormats')]
    public function test_strict_money_conversion(string $amount, ?string $expected): void
    {
        $method = new ReflectionMethod(PaddleAdjustmentProcessor::class, 'paymentMinorUnits');
        $this->assertSame($expected, $method->invoke(app(PaddleAdjustmentProcessor::class), $amount));
    }

    public static function moneyFormats(): array
    {
        return [
            ['100.00', '10000'], ['0.01', '1'], ['0001.00', '100'], ['100', null], ['100.0', null],
            ['100.000', null], ['1e2', null], ['-100.00', null], ['+100.00', null], [' 100.00', null],
            ['100,00', null], ['0.00', null], ['10000000000.00', null],
        ];
    }
}
