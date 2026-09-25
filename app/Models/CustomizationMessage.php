<?php

namespace App\Models;

use Database\Factories\CustomizationMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'customization_request_id',
    'user_id',
    'message',
    'read_at',
])]
class CustomizationMessage extends Model
{
    /** @use HasFactory<CustomizationMessageFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function customizationRequest(): BelongsTo
    {
        return $this->belongsTo(CustomizationRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}