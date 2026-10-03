<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\PaymentAdjustment;
use App\Models\ProductLicense;
use App\Payments\AdjustmentPurchaseLocks;
use App\Payments\Exceptions\AdjustmentAttributionChanged;
use App\Payments\PaymentEntitlements;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\AdjustmentTestCase;

class PaymentEntitlementHoldTest extends AdjustmentTestCase
{
    public function test_current_locked_payments_determine_verified_uniqueness(): void
    {
        $other = $this->payment->replicate();
        $other->payment_number = 'PAY-SECOND';
        $other->provider_payment_id = 'txn_second';
        $other->status = PaymentStatus::Pending;
        $other->save();
        $changed = false;
        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$changed, &$queries, $other): void {
            $queries[] = $query->sql;
            if (! $changed && str_starts_with($query->sql, 'select') && str_contains($query->sql, 'from "orders"')) {
                $changed = true;
                DB::table('payments')->where('id', $other->id)->update(['status' => PaymentStatus::Verified->value]);
            }
        });
        $ledger = $this->process();
        $this->assertTrue($changed);
        $this->assertFalse($ledger->hold_active);
        $this->assertTrue($ledger->review_required);
        $this->assertCount(0, array_filter($queries, fn ($sql) => str_contains($sql, 'count(*)') && str_contains($sql, '"payments"')));
    }

    public function test_purchase_locks_precede_ledger_and_never_follow_it(): void
    {
        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query->sql;
        });
        $this->process();
        $orderIndex = array_find_key($queries, fn ($sql) => str_starts_with($sql, 'select') && str_contains($sql, 'from "orders"'));
        $paymentIndex = array_find_key($queries, fn ($sql) => str_contains($sql, 'from "payments"') && str_contains($sql, '"payable_type"'));
        $ledgerIndex = array_find_key($queries, fn ($sql) => str_starts_with($sql, 'insert') && str_contains($sql, '"payment_adjustments"'));
        $this->assertNotNull($orderIndex);
        $this->assertNotNull($paymentIndex);
        $this->assertNotNull($ledgerIndex);
        $this->assertLessThan($paymentIndex, $orderIndex);
        $this->assertLessThan($ledgerIndex, $paymentIndex);
        $this->assertCount(0, array_filter(array_slice($queries, $ledgerIndex + 1), fn ($sql) => str_contains($sql, 'from "payments"') || str_contains($sql, 'from "orders"')));
    }

    public function test_attribution_retry_rolls_back_all_writes_before_retry(): void
    {
        $attempts = 0;
        app(AdjustmentPurchaseLocks::class)->transaction(function () use (&$attempts): void {
            $attempts++;
            $this->assertSame(1, DB::transactionLevel());
            $this->assertNull($this->order->fresh()->notes);
            $this->order->update(['notes' => 'attempt-'.$attempts]);
            if ($attempts === 1) {
                throw new AdjustmentAttributionChanged('Simulated attribution change');
            }
        });
        $this->assertSame(2, $attempts);
        $this->assertSame(0, DB::transactionLevel());
        $this->assertSame('attempt-2', $this->order->fresh()->notes);
    }

    public function test_held_license_cannot_activate_or_validate_and_domain_history_survives(): void
    {
        $this->process();
        foreach (['licenses.activate', 'licenses.validate'] as $route) {
            $this->postJson(route($route), ['license_key' => $this->license->license_key, 'product_key' => $this->product->license_product_key, 'domain' => 'example.com'])
                ->assertForbidden()->assertJsonPath('status', 'payment_hold');
        }
        $this->assertNull($this->license->fresh()->production_domain);
        $this->assertSame('unactivated', $this->license->fresh()->status);
        $this->license->update(['status' => 'active', 'production_domain' => 'example.com', 'activated_at' => now()]);
        $this->postJson(route('licenses.validate'), ['license_key' => $this->license->license_key, 'product_key' => $this->product->license_product_key, 'domain' => 'example.com'])
            ->assertForbidden()->assertJsonPath('status', 'payment_hold');
        $this->assertSame('example.com', $this->license->fresh()->production_domain);
        $this->assertSame(3, $this->license->activities()->where('event', 'payment_hold_attempt')->count());
    }

    public function test_other_independent_purchase_can_download_and_use_its_license(): void
    {
        $release = $this->release();
        $this->process();
        $url = route('customer.products.releases.download', [$this->product, $release]);
        $this->actingAs($this->customer)->get($url)->assertForbidden();
        [$otherOrder, , , $otherLicense] = $this->purchase();
        $this->assertFalse(app(PaymentEntitlements::class)->orderIsHeld($otherOrder->id));
        $this->get($url)->assertOk();
        $this->postJson(route('licenses.activate'), ['license_key' => $otherLicense->license_key, 'product_key' => $otherOrder->product->license_product_key, 'domain' => 'other.example'])
            ->assertOk()->assertJsonPath('valid', true);
        $this->postJson(route('licenses.validate'), ['license_key' => $otherLicense->license_key, 'product_key' => $otherOrder->product->license_product_key, 'domain' => 'other.example'])
            ->assertOk();
        $this->postJson(route('licenses.activate'), ['license_key' => $this->license->license_key, 'product_key' => $this->product->license_product_key, 'domain' => 'first.example'])
            ->assertForbidden();
        $otherLicense->update(['status' => 'revoked']);
        $this->get($url)->assertForbidden();
    }

    public function test_manual_revocation_survives_hold_restoration(): void
    {
        $this->license->update([
            'status' => 'revoked', 'production_domain' => 'example.com',
            'revoked_at' => now(), 'revoked_by' => $this->admin->id, 'revocation_reason' => 'Manual security decision',
        ]);
        $before = $this->license->fresh()->toArray();
        $ledger = $this->process();
        $this->decide($ledger, 'restore')->assertRedirect();
        foreach (['licenses.activate', 'licenses.validate'] as $route) {
            $this->postJson(route($route), ['license_key' => $this->license->license_key, 'product_key' => $this->product->license_product_key, 'domain' => 'example.com'])
                ->assertForbidden()->assertJsonPath('status', 'revoked');
        }
        $this->assertSame($before, $this->license->fresh()->toArray());
    }

    public function test_held_order_cannot_complete_or_recover(): void
    {
        $this->process();
        $this->order->update(['status' => OrderStatus::Processing]);
        $this->actingAs($this->admin)->patch(route('admin.orders.status.update', $this->order), ['status' => 'completed'])
            ->assertSessionHasErrors('status');
        $this->assertSame(OrderStatus::Processing, $this->order->fresh()->status);
        $this->order->update(['status' => OrderStatus::Cancelled]);
        $this->post(route('admin.orders.recover-payment', $this->order), ['reason' => 'Customer requested recovery'])
            ->assertSessionHasErrors('reason');
        $this->assertSame(OrderStatus::Cancelled, $this->order->fresh()->status);
        $this->get(route('admin.orders.show', $this->order))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('order.payment_review.payment_hold', true)->where('order.payment_review.can_recover', false));
        $this->assertSame(1, ProductLicense::count());
    }

    public function test_admin_authorization_reason_audit_and_stale_form(): void
    {
        $ledger = $this->process();
        $this->actingAs($this->customer)->get(route('admin.payment-adjustments.index'))->assertForbidden();
        $this->post(route('admin.payment-adjustments.update', $ledger), [])->assertForbidden();
        $this->actingAs($this->admin)->get(route('admin.payment-adjustments.index'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/PaymentAdjustments/Index'));
        $this->get(route('admin.payment-adjustments.show', $ledger))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/PaymentAdjustments/Show'));
        $this->decide($ledger, 'restore', ['reason' => ''])->assertSessionHasErrors('reason');
        $this->decide($ledger, 'review')->assertRedirect();
        $this->decide($ledger, 'restore')->assertSessionHasErrors('reason');
        $entry = $ledger->fresh()->history[1];
        $this->assertSame($this->admin->id, $entry['admin_id']);
        $this->assertSame('review', $entry['action']);
        $this->assertSame('Reviewed provider evidence with customer.', $entry['reason']);
    }

    public function test_memory_schema_unique_adjustments_and_restrictive_foreign_keys(): void
    {
        $ledger = $this->process();
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $this->assertTrue(Schema::hasColumns('payment_adjustments', ['snapshot', 'history', 'provider_updated_at']));
        Schema::enableForeignKeyConstraints();
        try {
            DB::table('payments')->where('id', $this->payment->id)->delete();
            $this->fail('History foreign key allowed payment deletion');
        } catch (QueryException) {
            $this->assertNotNull($this->payment->fresh());
        }
        try {
            $duplicate = $ledger->replicate();
            $duplicate->save();
            $this->fail('Duplicate adjustment was accepted');
        } catch (QueryException) {
            $this->assertSame(1, PaymentAdjustment::count());
        }
    }
}
