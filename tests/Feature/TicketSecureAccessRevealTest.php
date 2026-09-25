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

class TicketSecureAccessRevealTest extends TestCase
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

    private function makeAdminHandoff(
        Ticket $ticket,
        User $admin
    ): TicketSecureAccess {
        return TicketSecureAccess::factory()->create([
            'ticket_id' => $ticket->id,
            'created_by' => $admin->id,
            'direction' => TicketSecureAccessDirection::AdminToCustomer,
            'type' => TicketSecureAccessType::ApplicationLogin,
            'label' => 'Temporary Application Login',
            'login_url' => 'https://example.test/login',
            'username' => 'customer-user',
            'secret' => 'temporary-password-789',
            'notes' => 'Temporary credentials from BizzSoft.',
            'status' => TicketSecureAccessStatus::Submitted,
            'submitted_at' => now(),
            'viewed_at' => null,
            'closed_at' => null,
        ]);
    }

    public function test_ticket_owner_can_reveal_admin_handoff(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();

        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        $secureAccess = $this->makeAdminHandoff(
            $ticket,
            $admin
        );

        $response = $this
            ->actingAs($customer)
            ->getJson(
                route(
                    'tickets.secure-access.reveal',
                    [$ticket, $secureAccess]
                )
            );

        $response
            ->assertOk()
            ->assertExactJson([
                'login_url' => 'https://example.test/login',
                'username' => 'customer-user',
                'secret' => 'temporary-password-789',
                'notes' => 'Temporary credentials from BizzSoft.',
            ]);
    }

    public function test_first_reveal_records_viewed_at(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();

        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        $secureAccess = $this->makeAdminHandoff(
            $ticket,
            $admin
        );

        $this->assertNull($secureAccess->viewed_at);

        $this
            ->actingAs($customer)
            ->getJson(
                route(
                    'tickets.secure-access.reveal',
                    [$ticket, $secureAccess]
                )
            )
            ->assertOk();

        $secureAccess->refresh();

        $this->assertNotNull($secureAccess->viewed_at);
    }

    public function test_repeated_reveal_does_not_change_first_viewed_at(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();

        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        $secureAccess = $this->makeAdminHandoff(
            $ticket,
            $admin
        );

        $this
            ->actingAs($customer)
            ->getJson(
                route(
                    'tickets.secure-access.reveal',
                    [$ticket, $secureAccess]
                )
            )
            ->assertOk();

        $secureAccess->refresh();

        $firstViewedAt = $secureAccess->viewed_at->copy();

        $this
            ->actingAs($customer)
            ->getJson(
                route(
                    'tickets.secure-access.reveal',
                    [$ticket, $secureAccess]
                )
            )
            ->assertOk();

        $secureAccess->refresh();

        $this->assertTrue(
            $secureAccess->viewed_at->equalTo($firstViewedAt)
        );
    }

    public function test_other_customer_cannot_reveal_handoff(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeCustomer();
        $otherCustomer = $this->makeCustomer();

        $ticket = Ticket::factory()->create([
            'user_id' => $owner->id,
        ]);

        $secureAccess = $this->makeAdminHandoff(
            $ticket,
            $admin
        );

        $this
            ->actingAs($otherCustomer)
            ->getJson(
                route(
                    'tickets.secure-access.reveal',
                    [$ticket, $secureAccess]
                )
            )
            ->assertForbidden();

        $secureAccess->refresh();

        $this->assertNull($secureAccess->viewed_at);
    }

    public function test_customer_cannot_reveal_customer_to_admin_credentials(): void
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
            'status' => TicketSecureAccessStatus::Submitted,
            'submitted_at' => now(),
            'viewed_at' => null,
        ]);

        $this
            ->actingAs($customer)
            ->getJson(
                route(
                    'tickets.secure-access.reveal',
                    [$ticket, $secureAccess]
                )
            )
            ->assertForbidden();

        $secureAccess->refresh();

        $this->assertNull($secureAccess->viewed_at);
    }

    public function test_closed_handoff_cannot_be_revealed(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();

        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        $secureAccess = TicketSecureAccess::factory()->create([
            'ticket_id' => $ticket->id,
            'created_by' => $admin->id,
            'direction' => TicketSecureAccessDirection::AdminToCustomer,
            'type' => TicketSecureAccessType::ApplicationLogin,
            'status' => TicketSecureAccessStatus::Closed,
            'login_url' => null,
            'username' => null,
            'secret' => null,
            'notes' => null,
            'submitted_at' => now()->subMinute(),
            'closed_at' => now(),
        ]);

        $this
            ->actingAs($customer)
            ->getJson(
                route(
                    'tickets.secure-access.reveal',
                    [$ticket, $secureAccess]
                )
            )
            ->assertStatus(422);
    }

    public function test_secure_access_from_different_ticket_cannot_be_revealed(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();

        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        $differentTicket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        $secureAccess = $this->makeAdminHandoff(
            $differentTicket,
            $admin
        );

        $this
            ->actingAs($customer)
            ->getJson(
                route(
                    'tickets.secure-access.reveal',
                    [$ticket, $secureAccess]
                )
            )
            ->assertNotFound();

        $secureAccess->refresh();

        $this->assertNull($secureAccess->viewed_at);
    }

    public function test_guest_cannot_reveal_admin_handoff(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();

        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        $secureAccess = $this->makeAdminHandoff(
            $ticket,
            $admin
        );

        $this
            ->get(
                route(
                    'tickets.secure-access.reveal',
                    [$ticket, $secureAccess]
                )
            )
            ->assertRedirect(route('login'));

        $secureAccess->refresh();

        $this->assertNull($secureAccess->viewed_at);
    }
}