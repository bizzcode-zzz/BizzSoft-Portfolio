<?php

namespace Database\Factories;

use App\Enums\CustomizationSecureAccessDirection;
use App\Enums\CustomizationSecureAccessStatus;
use App\Enums\CustomizationSecureAccessType;
use App\Models\CustomizationRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CustomizationSecureAccess>
 */
class CustomizationSecureAccessFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customization_request_id' => CustomizationRequest::factory(),
            'created_by' => User::factory(),

            'direction' => CustomizationSecureAccessDirection::CustomerToAdmin,
            'type' => CustomizationSecureAccessType::Hosting,
            'label' => fake()->words(3, true),

            'login_url' => fake()->url(),
            'username' => fake()->userName(),
            'secret' => fake()->password(16, 24),
            'notes' => fake()->sentence(),

            'status' => CustomizationSecureAccessStatus::Submitted,
            'submitted_at' => now(),
            'viewed_at' => null,
            'closed_at' => null,
        ];
    }
}