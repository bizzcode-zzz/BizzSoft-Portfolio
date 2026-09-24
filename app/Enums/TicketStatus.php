<?php

namespace App\Enums;

enum TicketStatus: string
{
    case WaitingForAdmin = 'waiting_for_admin';
    case WaitingForCustomer = 'waiting_for_customer';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::WaitingForAdmin => 'Waiting for Admin',
            self::WaitingForCustomer => 'Waiting for Customer',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
        };
    }
}