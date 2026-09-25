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
use Tests\TestCase;

class AdminTicketSecureAccessTest extends TestCase
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

    public function test_admin_can_request_secure_access_for_ticket(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();

        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(
                route('admin.tickets.secure-access.store', $ticket),
                [
                    'type' => TicketSecureAccessType::ApplicationLogin->value,
                    'label' => 'Inventory Script Admin Access',
                ]
            );

        $response->assertRedirect();

        $secureAccess = TicketSecureAccess::query()
            ->where('ticket_id', $ticket->id)
            ->firstOrFail();

        $this->assertSame(
            $admin->id,
            $secureAccess->created_by
        );

        $this->assertSame(
            TicketSecureAccessDirection::CustomerToAdmin,
            $secureAccess->direction
        );

        $this->assertSame(
            TicketSecureAccessType::ApplicationLogin,
            $secureAccess->type
        );

        $this->assertSame(
            'Inventory Script Admin Access',
            $secureAccess->label
        );

        $this->assertSame(
            TicketSecureAccessStatus::Requested,
            $secureAccess->status
        );

        $this->assertNull($secureAccess->login_url);
        $this->assertNull($secureAccess->username);
        $this->assertNull($secureAccess->secret);
        $this->assertNull($secureAccess->notes);
        $this->assertNull($secureAccess->submitted_at);
        $this->assertNull($secureAccess->viewed_at);
        $this->assertNull($secureAccess->closed_at);
    }

    public function test_customer_cannot_use_admin_secure_access_request_route(): void
    {
        $customer = $this->makeCustomer();

        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        $response = $this
            ->actingAs($customer)
            ->post(
                route('admin.tickets.secure-access.store', $ticket),
                [
                    'type' => TicketSecureAccessType::ApplicationLogin->value,
                    'label' => 'Inventory Script Admin Access',
                ]
            );

        $response->assertForbidden();

        $this->assertDatabaseCount(
            'ticket_secure_accesses',
            0
        );
    }

    public function test_guest_cannot_request_ticket_secure_access(): void
    {
        $customer = $this->makeCustomer();

        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        $response = $this->post(
            route('admin.tickets.secure-access.store', $ticket),
            [
                'type' => TicketSecureAccessType::ApplicationLogin->value,
                'label' => 'Inventory Script Admin Access',
            ]
        );

        $response->assertRedirect(route('login'));

        $this->assertDatabaseCount(
            'ticket_secure_accesses',
            0
        );
    }

    public function test_secure_access_request_requires_valid_type(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();

        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.tickets.show', $ticket))
            ->post(
                route('admin.tickets.secure-access.store', $ticket),
                [
                    'type' => 'invalid-type',
                    'label' => 'Inventory Script Admin Access',
                ]
            );

        $response->assertRedirect(
            route('admin.tickets.show', $ticket)
        );

        $response->assertSessionHasErrors('type');

        $this->assertDatabaseCount(
            'ticket_secure_accesses',
            0
        );
    }

    public function test_secure_access_request_requires_label(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();

        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.tickets.show', $ticket))
            ->post(
                route('admin.tickets.secure-access.store', $ticket),
                [
                    'type' => TicketSecureAccessType::ApplicationLogin->value,
                    'label' => '',
                ]
            );

        $response->assertRedirect(
            route('admin.tickets.show', $ticket)
        );

        $response->assertSessionHasErrors('label');

        $this->assertDatabaseCount(
            'ticket_secure_accesses',
            0
        );
    }

    public function test_requesting_secure_access_does_not_change_ticket_status(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();

        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        $originalStatus = $ticket->status;

        $this
            ->actingAs($admin)
            ->post(
                route('admin.tickets.secure-access.store', $ticket),
                [
                    'type' => TicketSecureAccessType::ApplicationLogin->value,
                    'label' => 'Inventory Script Admin Access',
                ]
            )
            ->assertRedirect();

        $ticket->refresh();

        $this->assertSame(
            $originalStatus,
            $ticket->status
        );
    }
}