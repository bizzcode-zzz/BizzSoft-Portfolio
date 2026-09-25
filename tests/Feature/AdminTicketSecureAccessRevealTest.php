<?php

namespace Tests\Feature;

use App\Enums\TicketSecureAccessDirection;
use App\Enums\TicketSecureAccessStatus;
use App\Models\Ticket;
use App\Models\TicketSecureAccess;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTicketSecureAccessRevealTest extends TestCase
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

    private function makeSubmittedAccess(
        Ticket $ticket,
        User $customer
    ): TicketSecureAccess {
        return TicketSecureAccess::factory()->create([
            'ticket_id' => $ticket->id,
            'created_by' => $customer->id,
            'direction' => TicketSecureAccessDirection::CustomerToAdmin,
            'status' => TicketSecureAccessStatus::Submitted,
            'login_url' => 'https://example.test/admin',
            'username' => 'support-user',
            'secret' => 'temporary-password-123',
            'notes' => 'Temporary support access.',
            'submitted_at' => now(),
            'viewed_at' => null,
            'closed_at' => null,
        ]);
    }

    public function test_admin_can_deliberately_reveal_customer_credentials(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();

        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        $secureAccess = $this->makeSubmittedAccess(
            $ticket,
            $customer
        );

        $response = $this
            ->actingAs($admin)
            ->getJson(
                route(
                    'admin.tickets.secure-access.reveal',
                    [$ticket, $secureAccess]
                )
            );

        $response
            ->assertOk()
            ->assertExactJson([
                'login_url' => 'https://example.test/admin',
                'username' => 'support-user',
                'secret' => 'temporary-password-123',
                'notes' => 'Temporary support access.',
            ]);
    }

    public function test_first_reveal_records_viewed_at(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();

        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        $secureAccess = $this->makeSubmittedAccess(
            $ticket,
            $customer
        );

        $this->assertNull($secureAccess->viewed_at);

        $this
            ->actingAs($admin)
            ->getJson(
                route(
                    'admin.tickets.secure-access.reveal',
                    [$ticket, $secureAccess]
                )
            )
            ->assertOk();

        $secureAccess->refresh();

        $this->assertNotNull($secureAccess->viewed_at);
    }

    public function test_repeated_reveal_does_not_replace_first_view_timestamp(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();

        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        $secureAccess = $this->makeSubmittedAccess(
            $ticket,
            $customer
        );

        $secureAccess->update([
            'viewed_at' => now()->subMinutes(10),
        ]);

        $secureAccess->refresh();

        /*
         * Capture the exact timestamp after the database round-trip.
         * This avoids false failures caused by database timestamp precision.
         */
        $firstViewedAt = $secureAccess->viewed_at->copy();

        $this
            ->actingAs($admin)
            ->getJson(
                route(
                    'admin.tickets.secure-access.reveal',
                    [$ticket, $secureAccess]
                )
            )
            ->assertOk();

        $secureAccess->refresh();

        $this->assertTrue(
            $secureAccess->viewed_at->equalTo($firstViewedAt)
        );
    }

    public function test_customer_cannot_use_admin_reveal_endpoint(): void
    {
        $customer = $this->makeCustomer();

        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        $secureAccess = $this->makeSubmittedAccess(
            $ticket,
            $customer
        );

        $response = $this
            ->actingAs($customer)
            ->getJson(
                route(
                    'admin.tickets.secure-access.reveal',
                    [$ticket, $secureAccess]
                )
            );

        $response->assertForbidden();

        $secureAccess->refresh();

        $this->assertNull($secureAccess->viewed_at);
    }

    public function test_admin_cannot_reveal_requested_access(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();

        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        $secureAccess = TicketSecureAccess::factory()->create([
            'ticket_id' => $ticket->id,
            'created_by' => $admin->id,
            'direction' => TicketSecureAccessDirection::CustomerToAdmin,
            'status' => TicketSecureAccessStatus::Requested,
            'login_url' => null,
            'username' => null,
            'secret' => null,
            'notes' => null,
            'submitted_at' => null,
            'viewed_at' => null,
            'closed_at' => null,
        ]);

        $response = $this
            ->actingAs($admin)
            ->getJson(
                route(
                    'admin.tickets.secure-access.reveal',
                    [$ticket, $secureAccess]
                )
            );

        $response->assertUnprocessable();

        $secureAccess->refresh();

        $this->assertNull($secureAccess->viewed_at);
    }

    public function test_admin_cannot_reveal_closed_access(): void
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
            'status' => TicketSecureAccessStatus::Closed,
            'login_url' => null,
            'username' => null,
            'secret' => null,
            'notes' => null,
            'submitted_at' => now()->subHour(),
            'viewed_at' => now()->subMinutes(30),
            'closed_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin)
            ->getJson(
                route(
                    'admin.tickets.secure-access.reveal',
                    [$ticket, $secureAccess]
                )
            );

        $response->assertUnprocessable();
    }

    public function test_secure_access_from_different_ticket_cannot_be_revealed(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();

        $firstTicket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        $secondTicket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        $secureAccess = $this->makeSubmittedAccess(
            $firstTicket,
            $customer
        );

        $response = $this
            ->actingAs($admin)
            ->getJson(
                route(
                    'admin.tickets.secure-access.reveal',
                    [$secondTicket, $secureAccess]
                )
            );

        $response->assertNotFound();

        $secureAccess->refresh();

        $this->assertNull($secureAccess->viewed_at);
    }

    public function test_reveal_does_not_change_ticket_status(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();

        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        $originalStatus = $ticket->status;

        $secureAccess = $this->makeSubmittedAccess(
            $ticket,
            $customer
        );

        $this
            ->actingAs($admin)
            ->getJson(
                route(
                    'admin.tickets.secure-access.reveal',
                    [$ticket, $secureAccess]
                )
            )
            ->assertOk();

        $ticket->refresh();

        $this->assertSame(
            $originalStatus,
            $ticket->status
        );
    }
}