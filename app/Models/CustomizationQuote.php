<?php

namespace App\Models;

use Database\Factories\CustomizationQuoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CustomizationQuote extends Model
{
    /** @use HasFactory<CustomizationQuoteFactory> */
    use HasFactory;

    protected $fillable = [
        'customization_request_id',
        'price',
        'scope',
        'estimated_delivery',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'estimated_delivery' => 'date',
        ];
    }

    public function customizationRequest(): BelongsTo
    {
        return $this->belongsTo(CustomizationRequest::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }
}