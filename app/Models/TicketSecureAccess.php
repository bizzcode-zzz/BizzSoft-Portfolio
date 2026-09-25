<?php

namespace App\Models;

use App\Enums\TicketSecureAccessDirection;
use App\Enums\TicketSecureAccessStatus;
use App\Enums\TicketSecureAccessType;
use Database\Factories\TicketSecureAccessFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketSecureAccess extends Model
{
    /** @use HasFactory<TicketSecureAccessFactory> */
    use HasFactory;

    protected $fillable = [
        'ticket_id',
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
            'direction' => TicketSecureAccessDirection::class,
            'type' => TicketSecureAccessType::class,
            'status' => TicketSecureAccessStatus::class,

            'login_url' => 'encrypted',
            'username' => 'encrypted',
            'secret' => 'encrypted',
            'notes' => 'encrypted',

            'submitted_at' => 'datetime',
            'viewed_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}