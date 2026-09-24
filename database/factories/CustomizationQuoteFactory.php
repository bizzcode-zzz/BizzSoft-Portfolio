<?php

namespace Database\Factories;

use App\Models\CustomizationRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CustomizationQuote>
 */
class CustomizationQuoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customization_request_id' => CustomizationRequest::factory(),
            'price' => fake()->randomFloat(2, 500, 50000),
            'scope' => fake()->paragraph(),
            'estimated_delivery' => fake()->dateTimeBetween('+1 day', '+2 months'),
        ];
    }
}