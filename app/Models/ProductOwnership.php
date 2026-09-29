<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductOwnership extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'order_id',
        'starting_release_id',
        'granted_by',
        'granted_at',
    ];

    protected function casts(): array
    {
        return [
            'granted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function startingRelease(): BelongsTo
    {
        return $this->belongsTo(
            ProductRelease::class,
            'starting_release_id'
        );
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(ProductLicense::class);
    }

    public function grantor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
