<?php

namespace Tests\Feature;

use App\Enums\CustomizationRequestStatus;
use App\Enums\PaymentStatus;
use App\Models\CustomizationQuote;
use App\Models\CustomizationRequest;
use App\Models\Payment;
use App\Models\User;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Data\CheckoutSession;
use App\Payments\Exceptions\CheckoutProviderRejected;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\Support\UsesCheckoutDatabase;
use Tests\TestCase;

class CustomizationPaymentFlowTest extends TestCase
{
    use UsesCheckoutDatabase;

    public function test_accepted_quote_can_start_checkout(): void
    {
        $gateway = $this->bindFakeGateway();
        $customer = $this->createCustomer();

        [$customization, $quote] =
            $this->createCustomization(
                $customer,
                CustomizationRequestStatus::Accepted
            );

        $this
            ->actingAs($customer)
            ->withHeader('X-Inertia', 'true')
            ->post(
                route(
                    'customizations.payment.checkout',
                    $customization
                )
            )
            ->assertStatus(409)
            ->assertHeader(
                'X-Inertia-Location',
                CustomizationPaymentGatewayFake::CHECKOUT_URL
            );

        $this->assertSame(1, $gateway->calls);

        $payment = $quote
            ->payments()
            ->sole();

        $this->assertSame(
            CustomizationQuote::class,
            $payment->payable_type
        );
        $this->assertSame($quote->id, $payment->payable_id);
        $this->assertSame($customer->id, $payment->user_id);
        $this->assertSame('450.00', $payment->amount);
        $this->assertSame('USD', $payment->currency);
        $this->assertSame('paddle', $payment->provider);
        $this->assertSame(
            PaymentStatus::Pending,
            $payment->status
        );

        $this->assertSame(
            'customization_quote_checkout',
            data_get($payment->metadata, 'source')
        );

        $this->assertSame(
            'ready',
            data_get(
                $payment->metadata,
                'checkout_reservation.state'
            )
        );

        $this->assertSame(
            CustomizationPaymentGatewayFake::CHECKOUT_URL,
            data_get($payment->metadata, 'checkout_url')
        );
    }

    public function test_repeated_checkout_reuses_the_same_reservation(): void
    {
        $gateway = $this->bindFakeGateway();
        $customer = $this->createCustomer();

        [$customization, $quote] =
            $this->createCustomization(
                $customer,
                CustomizationRequestStatus::Accepted
            );

        $this->actingAs($customer);

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $this
                ->withHeader('X-Inertia', 'true')
                ->post(
                    route(
                        'customizations.payment.checkout',
                        $customization
                    )
                )
                ->assertStatus(409)
                ->assertHeader(
                    'X-Inertia-Location',
                    CustomizationPaymentGatewayFake::CHECKOUT_URL
                );
        }

        $this->assertSame(1, $gateway->calls);
        $this->assertSame(1, $quote->payments()->count());
    }

    public function test_definite_provider_rejection_marks_quote_payment_failed_and_allows_retry(): void
    {
        $rejectedGateway = new CustomizationRejectedGatewayFake;

        $this->app->instance(
            PaymentGateway::class,
            $rejectedGateway
        );

        $customer = $this->createCustomer();

        [$customization, $quote] =
            $this->createCustomization(
                $customer,
                CustomizationRequestStatus::Accepted
            );

        $this
            ->actingAs($customer)
            ->withHeader('X-Inertia', 'true')
            ->post(
                route(
                    'customizations.payment.checkout',
                    $customization
                )
            )
            ->assertStatus(502);

        $this->assertSame(1, $rejectedGateway->calls);

        $failedPayment = $quote
            ->payments()
            ->sole();

        $this->assertSame(
            PaymentStatus::Failed,
            $failedPayment->status
        );

        $this->assertSame(
            'confirmed_failed',
            data_get(
                $failedPayment->metadata,
                'checkout_reservation.state'
            )
        );

        $successfulGateway = $this->bindFakeGateway();

        $this
            ->actingAs($customer)
            ->withHeader('X-Inertia', 'true')
            ->post(
                route(
                    'customizations.payment.checkout',
                    $customization
                )
            )
            ->assertStatus(409)
            ->assertHeader(
                'X-Inertia-Location',
                CustomizationPaymentGatewayFake::CHECKOUT_URL
            );

        $this->assertSame(1, $successfulGateway->calls);
        $this->assertSame(2, $quote->payments()->count());

        $this->assertSame(
            1,
            $quote->payments()
                ->where('status', PaymentStatus::Failed->value)
                ->count()
        );

        $this->assertSame(
            1,
            $quote->payments()
                ->where('status', PaymentStatus::Pending->value)
                ->count()
        );
    }
    public function test_other_customer_cannot_pay_the_quote(): void
    {
        $gateway = $this->bindFakeGateway();

        $owner = $this->createCustomer();
        $otherCustomer = $this->createCustomer();

        [$customization, $quote] =
            $this->createCustomization(
                $owner,
                CustomizationRequestStatus::Accepted
            );

        $this
            ->actingAs($otherCustomer)
            ->post(
                route(
                    'customizations.payment.checkout',
                    $customization
                )
            )
            ->assertForbidden();

        $this->assertSame(0, $gateway->calls);
        $this->assertSame(0, $quote->payments()->count());
    }

    public function test_fully_paid_quote_cannot_start_another_checkout(): void
    {
        $gateway = $this->bindFakeGateway();
        $customer = $this->createCustomer();

        [$customization, $quote] =
            $this->createCustomization(
                $customer,
                CustomizationRequestStatus::Accepted
            );

        $quote->payments()->create([
            'payment_number' => 'PAY-CUSTOM-FULL-001',
            'user_id' => $customer->id,
            'amount' => '450.00',
            'currency' => 'USD',
            'provider' => 'paddle',
            'status' => PaymentStatus::Verified,
            'verified_at' => now(),
        ]);

        $this
            ->actingAs($customer)
            ->post(
                route(
                    'customizations.payment.checkout',
                    $customization
                )
            )
            ->assertStatus(422);

        $this->assertSame(0, $gateway->calls);
        $this->assertSame(1, $quote->payments()->count());
    }

    public function test_unaccepted_quote_cannot_start_checkout(): void
    {
        $gateway = $this->bindFakeGateway();
        $customer = $this->createCustomer();

        [$customization, $quote] =
            $this->createCustomization(
                $customer,
                CustomizationRequestStatus::QuoteSent
            );

        $this
            ->actingAs($customer)
            ->post(
                route(
                    'customizations.payment.checkout',
                    $customization
                )
            )
            ->assertStatus(422);

        $this->assertSame(0, $gateway->calls);
        $this->assertSame(0, $quote->payments()->count());
    }

    public function test_customization_page_reports_partial_payment_summary(): void
    {
        $customer = $this->createCustomer();

        [$customization, $quote] =
            $this->createCustomization(
                $customer,
                CustomizationRequestStatus::Accepted
            );

        $quote->payments()->create([
            'payment_number' => 'PAY-CUSTOM-PARTIAL-001',
            'user_id' => $customer->id,
            'amount' => '125.00',
            'currency' => 'USD',
            'provider' => 'manual',
            'status' => PaymentStatus::Verified,
            'verified_at' => now(),
        ]);

        $this
            ->actingAs($customer)
            ->get(route('customizations.show', $customization))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'Customer/Customizations/Show'
                    )
                    ->where(
                        'paymentSummary.verified_amount',
                        '125.00'
                    )
                    ->where(
                        'paymentSummary.remaining_amount',
                        '325.00'
                    )
                    ->where(
                        'paymentSummary.fully_paid',
                        false
                    )
            );
    }

    public function test_verified_full_payment_allows_admin_to_start_development(): void
    {
        $customer = $this->createCustomer();
        $admin = $this->createAdmin();

        [$customization, $quote] =
            $this->createCustomization(
                $customer,
                CustomizationRequestStatus::Accepted
            );

        $quote->payments()->create([
            'payment_number' => 'PAY-CUSTOM-VERIFIED-001',
            'user_id' => $customer->id,
            'amount' => '450.00',
            'currency' => 'USD',
            'provider' => 'paddle',
            'status' => PaymentStatus::Verified,
            'verified_at' => now(),
        ]);

        $this
            ->actingAs($admin)
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

        $this->assertDatabaseHas(
            'customization_requests',
            [
                'id' => $customization->id,
                'status' => CustomizationRequestStatus::InProgress->value,
            ]
        );
    }

    private function bindFakeGateway(): CustomizationPaymentGatewayFake
    {
        $gateway = new CustomizationPaymentGatewayFake;

        $this->app->instance(
            PaymentGateway::class,
            $gateway
        );

        return $gateway;
    }

    private function createCustomer(): User
    {
        Role::findOrCreate('customer');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        return $customer;
    }

    private function createAdmin(): User
    {
        Role::findOrCreate('admin');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    /**
     * @return array{CustomizationRequest, CustomizationQuote}
     */
    private function createCustomization(
        User $customer,
        CustomizationRequestStatus $status
    ): array {
        $customization = CustomizationRequest::create([
            'user_id' => $customer->id,
            'title' => 'Custom reporting module',
            'description' => 'Build a custom reporting workflow.',
            'status' => $status,
        ]);

        $quote = $customization
            ->quote()
            ->create([
                'price' => '450.00',
                'scope' => 'Custom reporting implementation.',
                'estimated_delivery' => now()
                    ->addWeeks(2)
                    ->toDateString(),
            ]);

        return [$customization, $quote];
    }
}

final class CustomizationRejectedGatewayFake implements PaymentGateway
{
    public int $calls = 0;

    public function provider(): string
    {
        return 'paddle';
    }

    public function createCheckout(
        Payment $payment,
        string $description
    ): CheckoutSession {
        $this->calls++;

        throw new CheckoutProviderRejected(
            'tax category not approved'
        );
    }
}
final class CustomizationPaymentGatewayFake implements PaymentGateway
{
    public const CHECKOUT_URL =
        'https://checkout.example.test/customization';

    public int $calls = 0;

    public function provider(): string
    {
        return 'paddle';
    }

    public function createCheckout(
        Payment $payment,
        string $description
    ): CheckoutSession {
        $this->calls++;

        return new CheckoutSession(
            provider: 'paddle',
            providerPaymentId: 'txn_customization_test_001',
            checkoutUrl: self::CHECKOUT_URL,
            metadata: [
                'test_description' => $description,
            ],
        );
    }
}
