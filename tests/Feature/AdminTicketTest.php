<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTicketTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_guest_cannot_access_admin_ticket_index(): void
    {
        $response = $this->get('/admin/tickets');

        $response->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_admin_ticket_index(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $response = $this
            ->actingAs($customer)
            ->get('/admin/tickets');

        $response->assertForbidden();
    }

    public function test_admin_can_access_ticket_index(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this
            ->actingAs($admin)
            ->get('/admin/tickets');

        $response->assertOk();
    }

    public function test_admin_ticket_index_contains_all_customer_tickets(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customerA = User::factory()->create();
        $customerA->assignRole('customer');

        $customerB = User::factory()->create();
        $customerB->assignRole('customer');

        Ticket::factory()
            ->for($customerA)
            ->create([
                'subject' => 'Customer A Ticket',
            ]);

        Ticket::factory()
            ->for($customerB)
            ->create([
                'subject' => 'Customer B Ticket',
            ]);

        $response = $this
            ->actingAs($admin)
            ->get('/admin/tickets');

        $response
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Tickets/Index', false)
                ->has('tickets', 2)
            );

        $response->assertSee('Customer A Ticket');
        $response->assertSee('Customer B Ticket');
    }

    public function test_admin_can_view_customer_ticket(): void
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
            ->get("/admin/tickets/{$ticket->id}");

        $response
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Tickets/Show', false)
                ->where('ticket.id', $ticket->id)
                ->where('ticket.user.id', $customer->id)
            );
    }

    public function test_customer_cannot_access_admin_ticket_show_route(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customer)
            ->create();

        $response = $this
            ->actingAs($customer)
            ->get("/admin/tickets/{$ticket->id}");

        $response->assertForbidden();
    }
}