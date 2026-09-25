<?php

namespace App\Enums;

enum TicketSecureAccessDirection: string
{
    case CustomerToAdmin = 'customer_to_admin';
    case AdminToCustomer = 'admin_to_customer';

    public function label(): string
    {
        return match ($this) {
            self::CustomerToAdmin => 'Customer → BizzSoft',
            self::AdminToCustomer => 'BizzSoft → Customer',
        };
    }
}