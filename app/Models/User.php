<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function ticketReplies(): HasMany
    {
        return $this->hasMany(TicketReply::class);
    }

    public function customizationRequests(): HasMany
    {
        return $this->hasMany(CustomizationRequest::class);
    }

    public function customizationMessages(): HasMany
    {
        return $this->hasMany(CustomizationMessage::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function productOwnerships(): HasMany
    {
        return $this->hasMany(ProductOwnership::class);
    }

    public function grantedProductOwnerships(): HasMany
    {
        return $this->hasMany(
            ProductOwnership::class,
            'granted_by',
        );
    }

    public function createdProductReleases(): HasMany
    {
        return $this->hasMany(
            ProductRelease::class,
            'created_by',
        );
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function verifiedPayments(): HasMany
    {
        return $this->hasMany(
            Payment::class,
            'verified_by',
        );
    }
}
