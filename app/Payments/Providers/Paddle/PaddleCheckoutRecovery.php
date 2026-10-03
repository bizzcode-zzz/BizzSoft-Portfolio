<?php

namespace App\Payments\Providers\Paddle;

use App\Models\Payment;
use App\Payments\CheckoutReservations;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class PaddleCheckoutRecovery
{
    public function __construct(
        private readonly PaddleClientFactory $clients,
        private readonly PaddleGateway $gateway,
        private readonly CheckoutReservations $reservations,
        private readonly PaddleTransactionValidator $validator,
    ) {}

    public function recover(Payment $payment, ?string $candidateId = null): Payment
    {
        if (DB::transactionLevel() !== 0) {
            throw new RuntimeException('Provider reconciliation must run outside a database transaction.');
        }

        $payment = $payment->fresh();
        if ($payment->provider !== 'paddle'
            || ! in_array(data_get($payment->metadata, 'checkout_reservation.state'), ['creating', 'outcome_unknown', 'ready'], true)
            || ! filled(data_get($payment->metadata, 'checkout_reservation.token'))) {
            throw new RuntimeException('This payment has no recoverable Paddle checkout reservation.');
        }

        $id = $payment->provider_payment_id ?? $candidateId;
        if (! filled($id) || ($candidateId !== null && $candidateId !== $id)) {
            throw new RuntimeException('Supply the matching Paddle transaction ID; an unknown outcome cannot be retried.');
        }

        // Read only. Unknown IDs must be located by an operator; absence never releases the reservation.
        $transaction = $this->clients->make()->transactions->get($id);
        if ($transaction->id !== $id) {
            throw new RuntimeException('Paddle returned a different transaction ID.');
        }
        $this->validator->validate($payment, $transaction);

        // Revalidate the freshly locked payment and exact reservation after the network request.
        return $this->reservations->finalize(
            $payment,
            $this->gateway->sessionFor($payment, $transaction),
            fn (Payment $locked) => $this->validator->validate($locked, $transaction),
        );
    }
}
