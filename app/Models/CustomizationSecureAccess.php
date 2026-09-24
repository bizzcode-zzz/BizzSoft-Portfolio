<?php

namespace App\Models;

use App\Enums\CustomizationSecureAccessDirection;
use App\Enums\CustomizationSecureAccessStatus;
use App\Enums\CustomizationSecureAccessType;
use Database\Factories\CustomizationSecureAccessFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomizationSecureAccess extends Model
{
    /** @use HasFactory<CustomizationSecureAccessFactory> */
    use HasFactory;

    protected $fillable = [
        'customization_request_id',
        'created_by',
        'direction',
        'type',
        'label',
        'login_url',
        'username',
        'secret',
        'notes',
        'status',
        'submitted_at',
        'viewed_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'direction' => CustomizationSecureAccessDirection::class,
            'type' => CustomizationSecureAccessType::class,

            'login_url' => 'encrypted',
            'username' => 'encrypted',
            'secret' => 'encrypted',
            'notes' => 'encrypted',

            'status' => CustomizationSecureAccessStatus::class,

            'submitted_at' => 'datetime',
            'viewed_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function customizationRequest(): BelongsTo
    {
        return $this->belongsTo(CustomizationRequest::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}