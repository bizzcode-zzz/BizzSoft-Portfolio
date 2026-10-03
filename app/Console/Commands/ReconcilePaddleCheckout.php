<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Payments\Providers\Paddle\PaddleCheckoutRecovery;
use Illuminate\Console\Command;

class ReconcilePaddleCheckout extends Command
{
    protected $signature = 'payments:reconcile-paddle {payment : Local payment ID} {--transaction= : Existing Paddle transaction ID when not saved locally}';

    protected $description = 'Recover an existing Paddle checkout reservation without creating or fulfilling a payment';

    public function handle(PaddleCheckoutRecovery $recovery): int
    {
        $payment = $recovery->recover(
            Payment::query()->findOrFail($this->argument('payment')),
            $this->option('transaction'),
        );

        $this->info('Reservation state: '.data_get($payment->metadata, 'checkout_reservation.state'));
        $this->line('Payment status was preserved. Any unmatched payment webhooks require provider redelivery.');

        return self::SUCCESS;
    }
}
