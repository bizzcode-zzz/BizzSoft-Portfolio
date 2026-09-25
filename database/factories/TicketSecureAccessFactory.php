<?php

namespace Database\Factories;

use App\Enums\TicketSecureAccessDirection;
use App\Enums\TicketSecureAccessStatus;
use App\Enums\TicketSecureAccessType;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\TicketSecureAccess>
 */
class TicketSecureAccessFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'created_by' => User::factory(),
            'direction' => TicketSecureAccessDirection::CustomerToAdmin,
            'type' => TicketSecureAccessType::ApplicationLogin,
            'label' => 'Temporary Application Access',
            'login_url' => fake()->url(),
            'username' => fake()->userName(),
            'secret' => 'temporary-secret',
            'notes' => 'Temporary secure access for support.',
            'status' => TicketSecureAccessStatus::Submitted,
            'submitted_at' => now(),
            'viewed_at' => null,
            'closed_at' => null,
        ];
    }
}