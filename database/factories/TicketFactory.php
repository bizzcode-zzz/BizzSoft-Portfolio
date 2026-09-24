<?php

namespace Database\Factories;

use App\Enums\TicketStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subject' => fake()->sentence(),
            'message' => fake()->paragraph(),
            'status' => TicketStatus::WaitingForAdmin,
        ];
    }
}