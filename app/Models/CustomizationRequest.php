<?php

namespace App\Models;

use App\Enums\CustomizationRequestStatus;
use Database\Factories\CustomizationRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CustomizationRequest extends Model
{
    /** @use HasFactory<CustomizationRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => CustomizationRequestStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CustomizationMessage::class);
    }

    public function quote(): HasOne
    {
        return $this->hasOne(CustomizationQuote::class);
    }

    public function secureAccesses(): HasMany
    {
        return $this->hasMany(CustomizationSecureAccess::class);
    }
}