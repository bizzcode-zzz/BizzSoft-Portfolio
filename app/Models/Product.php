<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use LogicException;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'short_description',
        'description',
        'price',
        'status',
        'version',
        'thumbnail_path',
        'demo_url',
        'built_with',
        'server_requirement',
        'database_system',
        'browser_support',
        'included_items',
    ];

    protected static function booted(): void
    {
        static::creating(function (Product $product): void {
            $product->license_product_key = (string) Str::uuid();
        });

        static::updating(function (Product $product): void {
            if ($product->isDirty('license_product_key')) {
                throw new LogicException(
                    'The license product key is immutable.'
                );
            }
        });
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'status' => ProductStatus::class,
            'included_items' => 'array',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function ownerships(): HasMany
    {
        return $this->hasMany(ProductOwnership::class);
    }

    public function releases(): HasMany
    {
        return $this->hasMany(ProductRelease::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
