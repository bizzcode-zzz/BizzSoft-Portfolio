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

class TicketSecureAccessInertiaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function makeCustomer(): User
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        return $customer;
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_customer_ticket_show_includes_secure_access_metadata(): void
    {
        $customer = $this->makeCustomer();
        $admin = $this->makeAdmin();

        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        $secureAccess = TicketSecureAccess::factory()->create([
            'ticket_id' => $ticket->id,
            'created_by' => $admin->id,
            'direction' => TicketSecureAccessDirection::AdminToCustomer,
            'type' => TicketSecureAccessType::ApplicationLogin,
            'label' => 'Temporary Application Login',
            'login_url' => 'https://example.test/login',
            'username' => 'customer-user',
            'secret' => 'super-secret-password',
            'notes' => 'Sensitive notes.',
            'status' => TicketSecureAccessStatus::Submitted,
            'submitted_at' => now(),
            'viewed_at' => null,
            'closed_at' => null,
        ]);

        $this
            ->actingAs($customer)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertInertia(
                fn(Assert $page) => $page
                    ->component('Customer/Tickets/Show')
                    ->where('ticket.id', $ticket->id)
                    ->has('ticket.secure_accesses', 1)
                    ->where(
                        'ticket.secure_accesses.0.id',
                        $secureAccess->id
                    )
                    ->where(
                        'ticket.secure_accesses.0.direction',
                        TicketSecureAccessDirection::AdminToCustomer->value
                    )
                    ->where(
                        'ticket.secure_accesses.0.type',
                        TicketSecureAccessType::ApplicationLogin->value
                    )
                    ->where(
                        'ticket.secure_accesses.0.label',
                        'Temporary Application Login'
                    )
                    ->where(
                        'ticket.secure_accesses.0.status',
                        TicketSecureAccessStatus::Submitted->value
                    )
            );
    }

    public function test_customer_ticket_show_does_not_expose_sensitive_credentials(): void
    {
        $customer = $this->makeCustomer();
        $admin = $this->makeAdmin();

        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        TicketSecureAccess::factory()->create([
            'ticket_id' => $ticket->id,
            'created_by' => $admin->id,
            'direction' => TicketSecureAccessDirection::AdminToCustomer,
            'type' => TicketSecureAccessType::ApplicationLogin,
            'label' => 'Sensitive Handoff',
            'login_url' => 'https://secret.example.test/login',
            'username' => 'secret-user',
            'secret' => 'never-expose-this-password',
            'notes' => 'never expose these sensitive notes',
            'status' => TicketSecureAccessStatus::Submitted,
            'submitted_at' => now(),
        ]);

        $response = $this
            ->actingAs($customer)
            ->get(route('tickets.show', $ticket));

        $response
            ->assertOk()
            ->assertInertia(
                fn(Assert $page) => $page
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
            'https://secret.example.test/login',
            false
        );

        $response->assertDontSee(
            'secret-user',
            false
        );

        $response->assertDontSee(
            'never-expose-this-password',
            false
        );

        $response->assertDontSee(
            'never expose these sensitive notes',
            false
        );
    }

    public function test_customer_ticket_show_only_contains_secure_access_for_that_ticket(): void
    {
        $customer = $this->makeCustomer();
        $admin = $this->makeAdmin();

        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        $otherTicket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        $expected = TicketSecureAccess::factory()->create([
            'ticket_id' => $ticket->id,
            'created_by' => $admin->id,
            'direction' => TicketSecureAccessDirection::AdminToCustomer,
            'type' => TicketSecureAccessType::ApplicationLogin,
            'label' => 'Expected Access',
            'status' => TicketSecureAccessStatus::Submitted,
        ]);

        TicketSecureAccess::factory()->create([
            'ticket_id' => $otherTicket->id,
            'created_by' => $admin->id,
            'direction' => TicketSecureAccessDirection::AdminToCustomer,
            'type' => TicketSecureAccessType::ApplicationLogin,
            'label' => 'Other Ticket Access',
            'status' => TicketSecureAccessStatus::Submitted,
        ]);

        $this
            ->actingAs($customer)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertInertia(
                fn(Assert $page) => $page
                    ->has('ticket.secure_accesses', 1)
                    ->where(
                        'ticket.secure_accesses.0.id',
                        $expected->id
                    )
                    ->where(
                        'ticket.secure_accesses.0.label',
                        'Expected Access'
                    )
            );
    }

    public function test_other_customer_cannot_access_ticket_secure_access_metadata(): void
    {
        $owner = $this->makeCustomer();
        $otherCustomer = $this->makeCustomer();
        $admin = $this->makeAdmin();

        $ticket = Ticket::factory()->create([
            'user_id' => $owner->id,
        ]);

        TicketSecureAccess::factory()->create([
            'ticket_id' => $ticket->id,
            'created_by' => $admin->id,
            'direction' => TicketSecureAccessDirection::AdminToCustomer,
            'type' => TicketSecureAccessType::ApplicationLogin,
            'label' => 'Private Ticket Access',
            'status' => TicketSecureAccessStatus::Submitted,
        ]);

        $this
            ->actingAs($otherCustomer)
            ->get(route('tickets.show', $ticket))
            ->assertForbidden();
    }
}