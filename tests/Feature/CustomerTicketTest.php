<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTicketTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_guest_cannot_access_customer_tickets(): void
    {
        $response = $this->get('/tickets');

        $response->assertRedirect(route('login'));
    }

    public function test_customer_can_access_ticket_index(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $response = $this
            ->actingAs($customer)
            ->get('/tickets');

        $response->assertOk();
    }

    public function test_admin_cannot_access_customer_ticket_index(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this
            ->actingAs($admin)
            ->get('/tickets');

        $response->assertForbidden();
    }

    public function test_customer_can_create_ticket(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $response = $this
            ->actingAs($customer)
            ->post('/tickets', [
                'subject' => 'Need help with installation',
                'message' => 'I need assistance installing my script.',
            ]);

        $ticket = Ticket::first();

        $this->assertNotNull($ticket);

        $this->assertSame(
            $customer->id,
            $ticket->user_id
        );

        $this->assertSame(
            'Need help with installation',
            $ticket->subject
        );

        $this->assertSame(
            TicketStatus::WaitingForAdmin,
            $ticket->status
        );

        $response->assertRedirect(
            route('tickets.show', $ticket)
        );
    }

    public function test_customer_cannot_assign_ticket_to_another_user(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $otherCustomer = User::factory()->create();
        $otherCustomer->assignRole('customer');

        $this
            ->actingAs($customer)
            ->post('/tickets', [
                'user_id' => $otherCustomer->id,
                'subject' => 'Ownership test',
                'message' => 'This ticket must belong to the authenticated customer.',
            ]);

        $ticket = Ticket::first();

        $this->assertNotNull($ticket);
        $this->assertSame($customer->id, $ticket->user_id);
        $this->assertNotSame($otherCustomer->id, $ticket->user_id);
    }

    public function test_ticket_subject_is_required(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $response = $this
            ->actingAs($customer)
            ->post('/tickets', [
                'subject' => '',
                'message' => 'Valid message.',
            ]);

        $response->assertSessionHasErrors('subject');

        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_ticket_message_is_required(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $response = $this
            ->actingAs($customer)
            ->post('/tickets', [
                'subject' => 'Valid subject',
                'message' => '',
            ]);

        $response->assertSessionHasErrors('message');

        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_customer_can_view_own_ticket(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customer)
            ->create();

        $response = $this
            ->actingAs($customer)
            ->get("/tickets/{$ticket->id}");

        $response->assertOk();
    }

    public function test_customer_cannot_view_another_customers_ticket(): void
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
            ->get("/tickets/{$ticket->id}");

        $response->assertForbidden();
    }

    public function test_ticket_index_only_contains_authenticated_customers_tickets(): void
    {
        $customerA = User::factory()->create();
        $customerA->assignRole('customer');

        $customerB = User::factory()->create();
        $customerB->assignRole('customer');

        $ticketA = Ticket::factory()
            ->for($customerA)
            ->create([
                'subject' => 'Customer A Ticket',
            ]);

        $ticketB = Ticket::factory()
            ->for($customerB)
            ->create([
                'subject' => 'Customer B Ticket',
            ]);

        $response = $this
            ->actingAs($customerA)
            ->get('/tickets');

        $response
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Customer/Tickets/Index')
                ->has('tickets', 1)
                ->where('tickets.0.id', $ticketA->id)
                ->where('tickets.0.subject', 'Customer A Ticket')
                ->missing('tickets.1')
            );

        $this->assertNotSame($ticketA->id, $ticketB->id);
    }
}