<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentAdjustment extends Model
{
    // Preserve microseconds in the provider version watermark.
    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $fillable = [
        'provider',
        'provider_adjustment_id',
        'provider_transaction_id',
        'payment_id',
        'order_id',
        'provider_updated_at',
        'last_event_id',
        'snapshot',
        'history',
        'hold_active',
        'review_required',
        'decision',
    ];

    protected function casts(): array
    {
        return [
            'provider_updated_at' => 'immutable_datetime',
            'snapshot' => 'array',
            'history' => 'array',
            'hold_active' => 'boolean',
            'review_required' => 'boolean',
        ];
    }
}
