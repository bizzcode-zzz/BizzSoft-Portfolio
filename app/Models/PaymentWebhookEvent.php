<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentWebhookEvent extends Model
{
    protected $fillable = [
        'provider',
        'event_id',
        'event_type',
        'provider_payment_id',
        'occurred_at',
        'processed_at',
        'failed_at',
        'failure_message',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'processed_at' => 'datetime',
            'failed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
