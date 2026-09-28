<?php

namespace App\Models;

use App\Enums\ProductReleaseStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRelease extends Model
{
    protected $fillable = [
        'product_id',
        'created_by',
        'version',
        'file_path',
        'original_name',
        'file_size',
        'sha256',
        'status',
        'released_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProductReleaseStatus::class,
            'released_at' => 'datetime',
            'file_size' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}