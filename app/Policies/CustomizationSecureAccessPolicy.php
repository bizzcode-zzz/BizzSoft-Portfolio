<?php

namespace App\Policies;

use App\Enums\CustomizationSecureAccessDirection;
use App\Models\CustomizationSecureAccess;
use App\Models\User;

class CustomizationSecureAccessPolicy
{
    public function view(User $user, CustomizationSecureAccess $secureAccess): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasRole('customer')
            && $secureAccess->customizationRequest->user_id === $user->id;
    }

    public function submit(User $user, CustomizationSecureAccess $secureAccess): bool
    {
        if ($secureAccess->direction === CustomizationSecureAccessDirection::CustomerToAdmin) {
            return $user->hasRole('customer')
                && $secureAccess->customizationRequest->user_id === $user->id;
        }

        if ($secureAccess->direction === CustomizationSecureAccessDirection::AdminToCustomer) {
            return $user->hasRole('admin');
        }

        return false;
    }

    public function reveal(User $user, CustomizationSecureAccess $secureAccess): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasRole('customer')
            && $secureAccess->direction === CustomizationSecureAccessDirection::AdminToCustomer
            && $secureAccess->customizationRequest->user_id === $user->id;
    }

    public function close(User $user, CustomizationSecureAccess $secureAccess): bool
    {
        return $user->hasRole('admin');
    }
}