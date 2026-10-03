<?php

namespace App\Payments\Providers\Paddle;

use App\Models\Payment;
use InvalidArgumentException;
use JsonSerializable;
use Paddle\SDK\Entities\Transaction as ApiTransaction;
use Paddle\SDK\Notifications\Entities\Transaction as PaddleTransaction;

final class PaddleTransactionValidator
{
    public function validate(
        Payment $payment,
        PaddleTransaction|ApiTransaction $transaction
    ): void {
        $currency = strtoupper(
            (string) $transaction
                ->currencyCode
                ->getValue()
        );

        if ($currency !== 'USD') {
            throw new InvalidArgumentException(
                "Unexpected Paddle currency [{$currency}]."
            );
        }

        if (
            strtoupper((string) $payment->currency) !==
            $currency
        ) {
            throw new InvalidArgumentException(
                'Paddle transaction currency does not match the local payment.'
            );
        }

        $discount = ltrim(
            (string) $transaction->details->totals->discount,
            '0'
        );

        if ($transaction->discountId !== null || ($discount !== '' && $discount !== '0')) {
            throw new InvalidArgumentException(
                'Paddle discounts are not supported for this payment.'
            );
        }

        // Tax is included in the agreed price; the final total must still match exactly.
        $expectedMinorUnits = $this->toMinorUnits(
            (string) $payment->amount
        );

        $providerTotal = ltrim(
            (string) $transaction
                ->details
                ->totals
                ->total,
            '0'
        );

        if ($providerTotal === '') {
            $providerTotal = '0';
        }

        if ($providerTotal !== $expectedMinorUnits) {
            throw new InvalidArgumentException(
                'Paddle transaction total does not match the local payment amount.'
            );
        }

        $customData = $this->customData(
            $transaction
        );

        $expected = [
            'bizzsoft_payment_id' => (string) $payment->id,

            'payment_number' => (string) $payment->payment_number,

            'payable_type' => (string) $payment->payable_type,

            'payable_id' => (string) $payment->payable_id,

            'user_id' => (string) $payment->user_id,
        ];

        foreach ($expected as $key => $value) {
            if (
                ! array_key_exists($key, $customData) ||
                (string) $customData[$key] !== $value
            ) {
                throw new InvalidArgumentException(
                    "Paddle custom data mismatch for [{$key}]."
                );
            }
        }
    }

    private function customData(
        PaddleTransaction|ApiTransaction $transaction
    ): array {
        $data = $transaction->customData?->data ?? [];

        if ($data instanceof JsonSerializable) {
            $data = $data->jsonSerialize();
        }

        return is_array($data)
            ? $data
            : [];
    }

    private function toMinorUnits(
        string $amount
    ): string {
        $amount = trim($amount);

        if (
            ! preg_match(
                '/^\d+(?:\.\d{1,2})?$/',
                $amount
            )
        ) {
            throw new InvalidArgumentException(
                'Payment amount must be a valid USD amount.'
            );
        }

        [$whole, $decimal] = array_pad(
            explode('.', $amount, 2),
            2,
            ''
        );

        $decimal = str_pad(
            $decimal,
            2,
            '0'
        );

        $minorUnits = ltrim(
            $whole.$decimal,
            '0'
        );

        return $minorUnits === ''
            ? '0'
            : $minorUnits;
    }
}
