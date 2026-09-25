<?php

namespace App\Enums;

enum TicketSecureAccessStatus: string
{
    case Requested = 'requested';
    case Submitted = 'submitted';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Requested',
            self::Submitted => 'Submitted',
            self::Closed => 'Closed',
        };
    }
}