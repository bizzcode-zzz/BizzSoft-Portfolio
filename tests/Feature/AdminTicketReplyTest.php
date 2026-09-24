<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTicketReplyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_admin_can_reply_to_customer_ticket(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customer)
            ->create([
                'status' => TicketStatus::WaitingForAdmin,
            ]);

        $response = $this
            ->actingAs($admin)
            ->post("/admin/tickets/{$ticket->id}/replies", [
                'message' => 'Please try reinstalling the script.',
            ]);

        $response->assertRedirect(
            route('admin.tickets.show', $ticket)
        );

        $this->assertDatabaseHas('ticket_replies', [
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'message' => 'Please try reinstalling the script.',
        ]);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => TicketStatus::WaitingForCustomer->value,
        ]);
    }

    public function test_admin_reply_changes_ticket_status_to_waiting_for_customer(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customer)
            ->create([
                'status' => TicketStatus::WaitingForAdmin,
            ]);

        $this
            ->actingAs($admin)
            ->post("/admin/tickets/{$ticket->id}/replies", [
                'message' => 'We need more information from you.',
            ]);

        $ticket->refresh();

        $this->assertSame(
            TicketStatus::WaitingForCustomer,
            $ticket->status
        );
    }

    public function test_customer_cannot_use_admin_reply_route(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customer)
            ->create();

        $response = $this
            ->actingAs($customer)
            ->post("/admin/tickets/{$ticket->id}/replies", [
                'message' => 'Attempted admin reply.',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('ticket_replies', [
            'ticket_id' => $ticket->id,
            'message' => 'Attempted admin reply.',
        ]);
    }

    public function test_guest_cannot_use_admin_reply_route(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customer)
            ->create();

        $response = $this
            ->post("/admin/tickets/{$ticket->id}/replies", [
                'message' => 'Guest reply attempt.',
            ]);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('ticket_replies', [
            'ticket_id' => $ticket->id,
            'message' => 'Guest reply attempt.',
        ]);
    }

    public function test_admin_reply_message_is_required(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customer)
            ->create();

        $response = $this
            ->actingAs($admin)
            ->post("/admin/tickets/{$ticket->id}/replies", [
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

    public function test_admin_cannot_assign_reply_to_another_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $otherAdmin = User::factory()->create();
        $otherAdmin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customer)
            ->create();

        $this
            ->actingAs($admin)
            ->post("/admin/tickets/{$ticket->id}/replies", [
                'user_id' => $otherAdmin->id,
                'message' => 'Secure admin reply.',
            ]);

        $this->assertDatabaseHas('ticket_replies', [
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'message' => 'Secure admin reply.',
        ]);

        $this->assertDatabaseMissing('ticket_replies', [
            'ticket_id' => $ticket->id,
            'user_id' => $otherAdmin->id,
            'message' => 'Secure admin reply.',
        ]);
    }

    public function test_admin_cannot_reply_to_closed_ticket(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customer)
            ->create([
                'status' => TicketStatus::Closed,
            ]);

        $response = $this
            ->actingAs($admin)
            ->post("/admin/tickets/{$ticket->id}/replies", [
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
}