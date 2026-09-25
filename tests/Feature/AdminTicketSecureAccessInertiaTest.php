<?php

namespace Tests\Feature;

use App\Enums\TicketSecureAccessDirection;
use App\Enums\TicketSecureAccessStatus;
use App\Enums\TicketSecureAccessType;
use App\Models\Ticket;
use App\Models\TicketSecureAccess;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminTicketSecureAccessInertiaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function makeCustomer(): User
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        return $customer;
    }

    public function test_admin_ticket_show_includes_secure_access_metadata(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();

        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        $secureAccess = TicketSecureAccess::factory()->create([
            'ticket_id' => $ticket->id,
            'created_by' => $customer->id,
            'direction' => TicketSecureAccessDirection::CustomerToAdmin,
            'type' => TicketSecureAccessType::ApplicationLogin,
            'label' => 'Customer Application Login',
            'login_url' => 'https://example.test/login',
            'username' => 'customer-user',
            'secret' => 'customer-secret-password',
            'notes' => 'Sensitive customer notes.',
            'status' => TicketSecureAccessStatus::Submitted,
            'submitted_at' => now(),
            'viewed_at' => null,
            'closed_at' => null,
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.tickets.show', $ticket))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Admin/Tickets/Show')
                    ->where('ticket.id', $ticket->id)
                    ->has('ticket.secure_accesses', 1)
                    ->where(
                        'ticket.secure_accesses.0.id',
                        $secureAccess->id
                    )
                    ->where(
                        'ticket.secure_accesses.0.direction',
                        TicketSecureAccessDirection::CustomerToAdmin->value
                    )
                    ->where(
                        'ticket.secure_accesses.0.type',
                        TicketSecureAccessType::ApplicationLogin->value
                    )
                    ->where(
                        'ticket.secure_accesses.0.label',
                        'Customer Application Login'
                    )
                    ->where(
                        'ticket.secure_accesses.0.status',
                        TicketSecureAccessStatus::Submitted->value
                    )
            );
    }

    public function test_admin_ticket_show_does_not_expose_sensitive_credentials(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();

        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        TicketSecureAccess::factory()->create([
            'ticket_id' => $ticket->id,
            'created_by' => $customer->id,
            'direction' => TicketSecureAccessDirection::CustomerToAdmin,
            'type' => TicketSecureAccessType::ApplicationLogin,
            'label' => 'Sensitive Customer Credentials',
            'login_url' => 'https://private.example.test/login',
            'username' => 'private-customer-user',
            'secret' => 'never-expose-admin-page-secret',
            'notes' => 'never expose these admin page notes',
            'status' => TicketSecureAccessStatus::Submitted,
            'submitted_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.tickets.show', $ticket));

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('ticket.secure_accesses', 1)
                    ->missing(
                        'ticket.secure_accesses.0.login_url'
                    )
                    ->missing(
                        'ticket.secure_accesses.0.username'
                    )
                    ->missing(
                        'ticket.secure_accesses.0.secret'
                    )
                    ->missing(
                        'ticket.secure_accesses.0.notes'
                    )
            );

        $response->assertDontSee(
            'https://private.example.test/login',
            false
        );

        $response->assertDontSee(
            'private-customer-user',
            false
        );

        $response->assertDontSee(
            'never-expose-admin-page-secret',
            false
        );

        $response->assertDontSee(
            'never expose these admin page notes',
            false
        );
    }

    public function test_admin_ticket_show_only_contains_secure_access_for_that_ticket(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();

        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        $otherTicket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        $expected = TicketSecureAccess::factory()->create([
            'ticket_id' => $ticket->id,
            'created_by' => $customer->id,
            'direction' => TicketSecureAccessDirection::CustomerToAdmin,
            'type' => TicketSecureAccessType::ApplicationLogin,
            'label' => 'Expected Ticket Access',
            'status' => TicketSecureAccessStatus::Submitted,
        ]);

        TicketSecureAccess::factory()->create([
            'ticket_id' => $otherTicket->id,
            'created_by' => $customer->id,
            'direction' => TicketSecureAccessDirection::CustomerToAdmin,
            'type' => TicketSecureAccessType::ApplicationLogin,
            'label' => 'Other Ticket Access',
            'status' => TicketSecureAccessStatus::Submitted,
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.tickets.show', $ticket))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('ticket.secure_accesses', 1)
                    ->where(
                        'ticket.secure_accesses.0.id',
                        $expected->id
                    )
                    ->where(
                        'ticket.secure_accesses.0.label',
                        'Expected Ticket Access'
                    )
            );
    }

    public function test_customer_cannot_access_admin_ticket_secure_access_metadata(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();

        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        TicketSecureAccess::factory()->create([
            'ticket_id' => $ticket->id,
            'created_by' => $customer->id,
            'direction' => TicketSecureAccessDirection::CustomerToAdmin,
            'type' => TicketSecureAccessType::ApplicationLogin,
            'label' => 'Private Admin Metadata',
            'status' => TicketSecureAccessStatus::Submitted,
        ]);

        $this
            ->actingAs($customer)
            ->get(route('admin.tickets.show', $ticket))
            ->assertForbidden();
    }
}