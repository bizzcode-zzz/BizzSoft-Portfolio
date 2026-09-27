<?php

namespace App\Payments\Contracts;

use App\Models\Payment;
use App\Payments\Data\CheckoutSession;

interface PaymentGateway
{
    public function provider(): string;

    public function createCheckout(
        Payment $payment,
        string $description,
    ): CheckoutSession;
}
