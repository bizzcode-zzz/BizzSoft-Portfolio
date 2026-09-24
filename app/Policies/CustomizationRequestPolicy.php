<?php

namespace App\Policies;

use App\Models\CustomizationRequest;
use App\Models\User;

class CustomizationRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'customer']);
    }

    public function view(User $user, CustomizationRequest $customizationRequest): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasRole('customer')
            && $user->id === $customizationRequest->user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('customer');
    }

    public function update(User $user, CustomizationRequest $customizationRequest): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasRole('customer')
            && $user->id === $customizationRequest->user_id;
    }

    public function delete(User $user, CustomizationRequest $customizationRequest): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, CustomizationRequest $customizationRequest): bool
    {
        return false;
    }

    public function forceDelete(User $user, CustomizationRequest $customizationRequest): bool
    {
        return false;
    }
}