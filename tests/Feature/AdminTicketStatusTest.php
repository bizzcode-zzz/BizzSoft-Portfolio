<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTicketStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_admin_can_resolve_ticket_waiting_for_admin(): void
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
            ->patch("/admin/tickets/{$ticket->id}/resolve");

        $response->assertRedirect(
            route('admin.tickets.show', $ticket)
        );

        $ticket->refresh();

        $this->assertSame(
            TicketStatus::Resolved,
            $ticket->status
        );
    }

    public function test_admin_can_resolve_ticket_waiting_for_customer(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customer)
            ->create([
                'status' => TicketStatus::WaitingForCustomer,
            ]);

        $this
            ->actingAs($admin)
            ->patch("/admin/tickets/{$ticket->id}/resolve")
            ->assertRedirect(
                route('admin.tickets.show', $ticket)
            );

        $ticket->refresh();

        $this->assertSame(
            TicketStatus::Resolved,
            $ticket->status
        );
    }

    public function test_admin_can_close_resolved_ticket(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customer)
            ->create([
                'status' => TicketStatus::Resolved,
            ]);

        $response = $this
            ->actingAs($admin)
            ->patch("/admin/tickets/{$ticket->id}/close");

        $response->assertRedirect(
            route('admin.tickets.show', $ticket)
        );

        $ticket->refresh();

        $this->assertSame(
            TicketStatus::Closed,
            $ticket->status
        );
    }

    public function test_admin_cannot_close_ticket_waiting_for_admin(): void
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
            ->patch("/admin/tickets/{$ticket->id}/close");

        $response->assertStatus(422);

        $ticket->refresh();

        $this->assertSame(
            TicketStatus::WaitingForAdmin,
            $ticket->status
        );
    }

    public function test_admin_cannot_close_ticket_waiting_for_customer(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customer)
            ->create([
                'status' => TicketStatus::WaitingForCustomer,
            ]);

        $response = $this
            ->actingAs($admin)
            ->patch("/admin/tickets/{$ticket->id}/close");

        $response->assertStatus(422);

        $ticket->refresh();

        $this->assertSame(
            TicketStatus::WaitingForCustomer,
            $ticket->status
        );
    }

    public function test_admin_cannot_resolve_closed_ticket(): void
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
            ->patch("/admin/tickets/{$ticket->id}/resolve");

        $response->assertStatus(422);

        $ticket->refresh();

        $this->assertSame(
            TicketStatus::Closed,
            $ticket->status
        );
    }

    public function test_customer_cannot_resolve_ticket(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customer)
            ->create();

        $response = $this
            ->actingAs($customer)
            ->patch("/admin/tickets/{$ticket->id}/resolve");

        $response->assertForbidden();

        $ticket->refresh();

        $this->assertSame(
            TicketStatus::WaitingForAdmin,
            $ticket->status
        );
    }

    public function test_customer_cannot_close_ticket(): void
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
            ->patch("/admin/tickets/{$ticket->id}/close");

        $response->assertForbidden();

        $ticket->refresh();

        $this->assertSame(
            TicketStatus::Resolved,
            $ticket->status
        );
    }

    public function test_guest_cannot_resolve_ticket(): void
    {
        $customer = User::factory()->create();

        $ticket = Ticket::factory()
            ->for($customer)
            ->create();

        $response = $this
            ->patch("/admin/tickets/{$ticket->id}/resolve");

        $response->assertRedirect(route('login'));

        $ticket->refresh();

        $this->assertSame(
            TicketStatus::WaitingForAdmin,
            $ticket->status
        );
    }

    public function test_guest_cannot_close_ticket(): void
    {
        $customer = User::factory()->create();

        $ticket = Ticket::factory()
            ->for($customer)
            ->create([
                'status' => TicketStatus::Resolved,
            ]);

        $response = $this
            ->patch("/admin/tickets/{$ticket->id}/close");

        $response->assertRedirect(route('login'));

        $ticket->refresh();

        $this->assertSame(
            TicketStatus::Resolved,
            $ticket->status
        );
    }
}