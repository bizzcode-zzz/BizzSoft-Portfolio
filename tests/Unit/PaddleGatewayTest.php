<?php

namespace Tests\Unit;

use App\Models\Payment;
use App\Payments\Providers\Paddle\PaddleClientFactory;
use App\Payments\Providers\Paddle\PaddleGateway;
use InvalidArgumentException;
use Tests\TestCase;

class PaddleGatewayTest extends TestCase
{
    public function test_provider_is_paddle(): void
    {
        $gateway = new PaddleGateway(
            app(PaddleClientFactory::class)
        );

        $this->assertSame(
            'paddle',
            $gateway->provider()
        );
    }

    public function test_it_rejects_non_usd_payments_before_calling_paddle(): void
    {
        $payment = new Payment([
            'payment_number' => 'PAY-PADDLE-TEST-001',
            'amount' => '349.00',
            'currency' => 'PHP',
        ]);

        $gateway = new PaddleGateway(
            app(PaddleClientFactory::class)
        );

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'Paddle payments must use USD.'
        );

        $gateway->createCheckout(
            $payment,
            'BizzSoft V5'
        );
    }

    public function test_it_rejects_empty_checkout_description_before_calling_paddle(): void
    {
        $payment = new Payment([
            'payment_number' => 'PAY-PADDLE-TEST-002',
            'amount' => '349.00',
            'currency' => 'USD',
        ]);

        $gateway = new PaddleGateway(
            app(PaddleClientFactory::class)
        );

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'Paddle checkout description is required.'
        );

        $gateway->createCheckout(
            $payment,
            '   '
        );
    }
}
