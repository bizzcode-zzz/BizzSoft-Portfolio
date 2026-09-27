<?php

namespace App\Payments;

use App\Models\Payment;
use Illuminate\Support\Str;
use RuntimeException;

final class PaymentNumberGenerator
{
    public function generate(): string
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $number = sprintf(
                'PAY-%s-%s',
                now()->format('Ymd'),
                Str::upper(Str::random(6))
            );

            if (
                ! Payment::query()
                    ->where('payment_number', $number)
                    ->exists()
            ) {
                return $number;
            }
        }

        throw new RuntimeException(
            'Unable to generate a unique payment number.'
        );
    }
}
