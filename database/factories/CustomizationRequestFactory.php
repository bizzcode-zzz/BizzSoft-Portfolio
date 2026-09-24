<?php

namespace Database\Factories;

use App\Enums\CustomizationRequestStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CustomizationRequest>
 */
class CustomizationRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(5),
            'description' => fake()->paragraph(),
            'status' => CustomizationRequestStatus::Submitted,
        ];
    }
}