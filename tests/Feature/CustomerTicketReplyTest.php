<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTicketReplyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_customer_can_reply_to_own_ticket(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customer)
            ->create();

        $response = $this
            ->actingAs($customer)
            ->post("/tickets/{$ticket->id}/replies", [
                'message' => 'Any update on this ticket?',
            ]);

        $reply = TicketReply::first();

        $this->assertNotNull($reply);
        $this->assertSame($ticket->id, $reply->ticket_id);
        $this->assertSame($customer->id, $reply->user_id);
        $this->assertSame('Any update on this ticket?', $reply->message);

        $response->assertRedirect(
            route('tickets.show', $ticket)
        );
    }

    public function test_customer_reply_changes_status_to_waiting_for_admin(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customer)
            ->create([
                'status' => TicketStatus::WaitingForCustomer,
            ]);

        $this
            ->actingAs($customer)
            ->post("/tickets/{$ticket->id}/replies", [
                'message' => 'Here is the information you requested.',
            ]);

        $ticket->refresh();

        $this->assertSame(
            TicketStatus::WaitingForAdmin,
            $ticket->status
        );

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => TicketStatus::WaitingForAdmin->value,
        ]);
    }

    public function test_customer_reply_reopens_resolved_ticket(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customer)
            ->create([
                'status' => TicketStatus::Resolved,
            ]);

        $response = $this
            ->actingAs($customer)
            ->post("/tickets/{$ticket->id}/replies", [
                'message' => 'The issue has happened again.',
            ]);

        $response->assertRedirect(
            route('tickets.show', $ticket)
        );

        $this->assertDatabaseHas('ticket_replies', [
            'ticket_id' => $ticket->id,
            'user_id' => $customer->id,
            'message' => 'The issue has happened again.',
        ]);

        $ticket->refresh();

        $this->assertSame(
            TicketStatus::WaitingForAdmin,
            $ticket->status
        );
    }

    public function test_customer_cannot_reply_to_closed_ticket(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customer)
            ->create([
                'status' => TicketStatus::Closed,
            ]);

        $response = $this
            ->actingAs($customer)
            ->post("/tickets/{$ticket->id}/replies", [
                'message' => 'This reply should not be saved.',
            ]);

        $response->assertStatus(422);

        $this->assertDatabaseMissing('ticket_replies', [
            'ticket_id' => $ticket->id,
            'message' => 'This reply should not be saved.',
        ]);

        $ticket->refresh();

        $this->assertSame(
            TicketStatus::Closed,
            $ticket->status
        );
    }

    public function test_customer_cannot_reply_to_another_customers_ticket(): void
    {
        $customerA = User::factory()->create();
        $customerA->assignRole('customer');

        $customerB = User::factory()->create();
        $customerB->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customerB)
            ->create();

        $response = $this
            ->actingAs($customerA)
            ->post("/tickets/{$ticket->id}/replies", [
                'message' => 'I should not be allowed to reply here.',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseCount('ticket_replies', 0);
    }

    public function test_guest_cannot_reply_to_ticket(): void
    {
        $customer = User::factory()->create();

        $ticket = Ticket::factory()
            ->for($customer)
            ->create();

        $response = $this->post(
            "/tickets/{$ticket->id}/replies",
            [
                'message' => 'Guest reply.',
            ]
        );

        $response->assertRedirect(route('login'));

        $this->assertDatabaseCount('ticket_replies', 0);
    }

    public function test_admin_cannot_use_customer_reply_route(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $ticket = Ticket::factory()
            ->for($customer)
            ->create();

        $response = $this
            ->actingAs($admin)
            ->post("/tickets/{$ticket->id}/replies", [
                'message' => 'Admin reply through customer route.',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseCount('ticket_replies', 0);
    }

    public function test_reply_message_is_required(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customer)
            ->create();

        $response = $this
            ->actingAs($customer)
            ->post("/tickets/{$ticket->id}/replies", [
                'message' => '',
            ]);

        $response->assertSessionHasErrors('message');

        $this->assertDatabaseCount('ticket_replies', 0);

        $ticket->refresh();

        $this->assertSame(
            TicketStatus::WaitingForAdmin,
            $ticket->status
        );
    }

    public function test_customer_cannot_assign_reply_to_another_user(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $otherCustomer = User::factory()->create();
        $otherCustomer->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customer)
            ->create();

        $this
            ->actingAs($customer)
            ->post("/tickets/{$ticket->id}/replies", [
                'user_id' => $otherCustomer->id,
                'message' => 'Ownership must remain with me.',
            ]);

        $reply = TicketReply::first();

        $this->assertNotNull($reply);
        $this->assertSame($customer->id, $reply->user_id);
        $this->assertNotSame($otherCustomer->id, $reply->user_id);
    }
}