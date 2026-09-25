<?php

namespace App\Policies;

use App\Enums\TicketSecureAccessDirection;
use App\Models\TicketSecureAccess;
use App\Models\User;

class TicketSecureAccessPolicy
{
    public function view(User $user, TicketSecureAccess $secureAccess): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasRole('customer')
            && $secureAccess->ticket->user_id === $user->id;
    }

    public function submit(User $user, TicketSecureAccess $secureAccess): bool
    {
        if (
            $secureAccess->direction
            === TicketSecureAccessDirection::CustomerToAdmin
        ) {
            return $user->hasRole('customer')
                && $secureAccess->ticket->user_id === $user->id;
        }

        if (
            $secureAccess->direction
            === TicketSecureAccessDirection::AdminToCustomer
        ) {
            return $user->hasRole('admin');
        }

        return false;
    }

    public function reveal(User $user, TicketSecureAccess $secureAccess): bool
    {
        if (
            $secureAccess->direction
            === TicketSecureAccessDirection::CustomerToAdmin
        ) {
            return $user->hasRole('admin');
        }

        if (
            $secureAccess->direction
            === TicketSecureAccessDirection::AdminToCustomer
        ) {
            return $user->hasRole('customer')
                && $secureAccess->ticket->user_id === $user->id;
        }

        return false;
    }

    public function close(User $user, TicketSecureAccess $secureAccess): bool
    {
        return $user->hasRole('admin');
    }
}