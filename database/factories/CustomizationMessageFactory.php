<?php

namespace Database\Factories;

use App\Models\CustomizationRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CustomizationMessage>
 */
class CustomizationMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customization_request_id' => CustomizationRequest::factory(),
            'user_id' => User::factory(),
            'message' => fake()->paragraph(),
        ];
    }
}