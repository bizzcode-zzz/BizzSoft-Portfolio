<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Models\CustomizationQuote;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_payment_can_belong_to_order_and_user_relationships_work(): void
    {
        $customer = User::factory()->create();
        $admin = User::factory()->create();

        $product = Product::create([
            'name' => 'BizzSoft V5',
            'slug' => 'bizzsoft-v5',
            'short_description' => 'Business management software.',
            'description' => 'BizzSoft product test record.',
            'price' => 20000,
            'status' => ProductStatus::Active,
            'version' => '5',
        ]);

        $order = Order::create([
            'order_number' => 'BS-PAYMENT-TEST-001',
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'product_version_snapshot' => $product->version,
            'price_snapshot' => $product->price,
            'status' => OrderStatus::AwaitingPayment,
            'ordered_at' => now(),
        ]);

        $payment = $order->payments()->create([
            'payment_number' => 'PAY-TEST-001',
            'user_id' => $customer->id,
            'amount' => '20000.00',
            'currency' => 'PHP',
            'provider' => 'manual',
            'method' => 'gcash',
            'reference_number' => 'GCASH-123456',
            'status' => PaymentStatus::Verified,
            'metadata' => [
                'source' => 'customer_submission',
            ],
            'submitted_at' => now(),
            'verified_at' => now(),
            'verified_by' => $admin->id,
        ]);

        $payment->refresh();

        $this->assertInstanceOf(
            Order::class,
            $payment->payable
        );

        $this->assertTrue(
            $payment->payable->is($order)
        );

        $this->assertTrue(
            $payment->user->is($customer)
        );

        $this->assertTrue(
            $payment->verifier->is($admin)
        );

        $this->assertSame(
            '20000.00',
            $payment->amount
        );

        $this->assertSame(
            'PHP',
            $payment->currency
        );

        $this->assertSame(
            'manual',
            $payment->provider
        );

        $this->assertSame(
            'gcash',
            $payment->method
        );

        $this->assertSame(
            PaymentStatus::Verified,
            $payment->status
        );

        $this->assertSame(
            [
                'source' => 'customer_submission',
            ],
            $payment->metadata
        );

        $this->assertNotNull(
            $payment->submitted_at
        );

        $this->assertNotNull(
            $payment->verified_at
        );

        $this->assertCount(
            1,
            $order->payments
        );

        $this->assertTrue(
            $order->payments->first()->is($payment)
        );

        $this->assertCount(
            1,
            $customer->payments
        );

        $this->assertTrue(
            $customer->payments->first()->is($payment)
        );

        $this->assertCount(
            1,
            $admin->verifiedPayments
        );

        $this->assertTrue(
            $admin->verifiedPayments->first()->is($payment)
        );

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'payment_number' => 'PAY-TEST-001',
            'payable_type' => Order::class,
            'payable_id' => $order->id,
            'user_id' => $customer->id,
            'amount' => '20000.00',
            'currency' => 'PHP',
            'provider' => 'manual',
            'method' => 'gcash',
            'reference_number' => 'GCASH-123456',
            'status' => PaymentStatus::Verified->value,
            'verified_by' => $admin->id,
        ]);
    }

    public function test_pending_payment_can_belong_to_customization_quote(): void
    {
        $customer = User::factory()->create();

        $quote = CustomizationQuote::factory()->create([
            'price' => '35000.00',
            'scope' => 'Custom inventory and reporting module.',
        ]);

        $payment = $quote->payments()->create([
            'payment_number' => 'PAY-QUOTE-TEST-001',
            'user_id' => $customer->id,
            'amount' => $quote->price,
            'currency' => 'PHP',
            'provider' => 'card_gateway',
            'method' => 'card',
            'provider_payment_id' => 'gateway_test_quote_001',
            'status' => PaymentStatus::Pending,
            'metadata' => [
                'source' => 'quotation',
            ],
        ]);

        $payment->refresh();

        $this->assertInstanceOf(
            CustomizationQuote::class,
            $payment->payable
        );

        $this->assertTrue(
            $payment->payable->is($quote)
        );

        $this->assertTrue(
            $payment->user->is($customer)
        );

        $this->assertNull(
            $payment->verifier
        );

        $this->assertNull(
            $payment->verified_at
        );

        $this->assertSame(
            '35000.00',
            $payment->amount
        );

        $this->assertSame(
            'card_gateway',
            $payment->provider
        );

        $this->assertSame(
            'card',
            $payment->method
        );

        $this->assertSame(
            PaymentStatus::Pending,
            $payment->status
        );

        $this->assertSame(
            [
                'source' => 'quotation',
            ],
            $payment->metadata
        );

        $this->assertCount(
            1,
            $quote->payments
        );

        $this->assertTrue(
            $quote->payments->first()->is($payment)
        );

        $this->assertCount(
            1,
            $customer->payments
        );

        $this->assertTrue(
            $customer->payments->first()->is($payment)
        );

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'payment_number' => 'PAY-QUOTE-TEST-001',
            'payable_type' => CustomizationQuote::class,
            'payable_id' => $quote->id,
            'user_id' => $customer->id,
            'amount' => '35000.00',
            'currency' => 'PHP',
            'provider' => 'card_gateway',
            'method' => 'card',
            'provider_payment_id' => 'gateway_test_quote_001',
            'status' => PaymentStatus::Pending->value,
            'verified_by' => null,
        ]);
    }
}