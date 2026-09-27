<?php

namespace App\Payments\Data;

final readonly class CheckoutSession
{
    public function __construct(
        public string $provider,
        public string $providerPaymentId,
        public ?string $checkoutUrl = null,
        public array $metadata = [],
    ) {
    }
}
