<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case AwaitingPayment = 'awaiting_payment';
    case Processing = 'processing';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::AwaitingPayment => 'Awaiting Payment',
            self::Processing => 'Processing',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function adminTransitionTargets(): array
    {
        return match ($this) {
            self::Pending => [
                self::AwaitingPayment,
                self::Cancelled,
            ],
            self::AwaitingPayment => [
                self::Cancelled,
            ],
            self::Processing => [
                self::Completed,
            ],
            self::Completed,
            self::Cancelled => [],
        };
    }

    public function canAdminTransitionTo(self $target): bool
    {
        return in_array(
            $target,
            $this->adminTransitionTargets(),
            true
        );
    }
}
