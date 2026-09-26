<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Submitted = 'submitted';
    case Verified = 'verified';
    case Failed = 'failed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Processing => 'Processing',
            self::Submitted => 'Submitted',
            self::Verified => 'Verified',
            self::Failed => 'Failed',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }
}