<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductLicense extends Model
{
    protected $fillable = [
        'product_ownership_id',
        'order_id',
        'license_key',
        'status',
        'production_domain',
        'activated_at',
        'last_validated_at',
        'revoked_at',
        'revoked_by',
        'revocation_reason',
    ];

    protected function casts(): array
    {
        return [
            'activated_at' => 'datetime',
            'last_validated_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function ownership(): BelongsTo
    {
        return $this->belongsTo(
            ProductOwnership::class,
            'product_ownership_id'
        );
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'revoked_by'
        );
    }
}