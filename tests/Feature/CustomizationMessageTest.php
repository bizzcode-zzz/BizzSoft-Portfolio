<?php

namespace Tests\Feature;

use App\Models\CustomizationMessage;
use App\Models\CustomizationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomizationMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_customization_message_can_be_created(): void
    {
        $request = CustomizationRequest::factory()->create();
        $user = User::factory()->create();

        $message = CustomizationMessage::factory()->create([
            'customization_request_id' => $request->id,
            'user_id' => $user->id,
            'message' => 'Please provide more information about the graph.',
        ]);

        $this->assertDatabaseHas('customization_messages', [
            'id' => $message->id,
            'customization_request_id' => $request->id,
            'user_id' => $user->id,
            'message' => 'Please provide more information about the graph.',
        ]);
    }

    public function test_customization_message_belongs_to_customization_request(): void
    {
        $request = CustomizationRequest::factory()->create();

        $message = CustomizationMessage::factory()->create([
            'customization_request_id' => $request->id,
        ]);

        $this->assertTrue(
            $message->customizationRequest->is($request)
        );
    }

    public function test_customization_message_belongs_to_user(): void
    {
        $user = User::factory()->create();

        $message = CustomizationMessage::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertTrue(
            $message->user->is($user)
        );
    }

    public function test_customization_request_has_many_messages(): void
    {
        $request = CustomizationRequest::factory()->create();

        CustomizationMessage::factory()
            ->count(3)
            ->create([
                'customization_request_id' => $request->id,
            ]);

        $this->assertCount(3, $request->messages);
    }

    public function test_user_has_many_customization_messages(): void
    {
        $user = User::factory()->create();

        CustomizationMessage::factory()
            ->count(2)
            ->create([
                'user_id' => $user->id,
            ]);

        $this->assertCount(2, $user->customizationMessages);
    }

    public function test_deleting_customization_request_deletes_its_messages(): void
    {
        $request = CustomizationRequest::factory()->create();

        $message = CustomizationMessage::factory()->create([
            'customization_request_id' => $request->id,
        ]);

        $request->delete();

        $this->assertDatabaseMissing('customization_messages', [
            'id' => $message->id,
        ]);
    }
}