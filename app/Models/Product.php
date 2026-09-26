<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'status' => ProductStatus::class,
            'included_items' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}