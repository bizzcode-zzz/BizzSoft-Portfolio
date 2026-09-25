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

class AdminTicketSecureAccessCloseTest extends TestCase
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
            'notes' => 'Temporary support credentials.',
            'submitted_at' => now()->subHour(),
            'viewed_at' => now()->subMinutes(30),
            'closed_at' => null,
        ]);
    }

    public function test_admin_can_close_submitted_secure_access(): void
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
            ->patch(
                route(
                    'admin.tickets.secure-access.close',
                    [$ticket, $secureAccess]
                )
            );

        $response->assertRedirect();

        $secureAccess->refresh();

        $this->assertSame(
            TicketSecureAccessStatus::Closed,
            $secureAccess->status
        );

        $this->assertNotNull($secureAccess->closed_at);
    }

    public function test_closing_secure_access_permanently_purges_sensitive_payload(): void
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

        $this
            ->actingAs($admin)
            ->patch(
                route(
                    'admin.tickets.secure-access.close',
                    [$ticket, $secureAccess]
                )
            )
            ->assertRedirect();

        $secureAccess->refresh();

        $this->assertNull($secureAccess->login_url);
        $this->assertNull($secureAccess->username);
        $this->assertNull($secureAccess->secret);
        $this->assertNull($secureAccess->notes);
    }

    public function test_closing_preserves_non_sensitive_audit_history(): void
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

        $originalId = $secureAccess->id;
        $originalTicketId = $secureAccess->ticket_id;
        $originalCreatedBy = $secureAccess->created_by;
        $originalDirection = $secureAccess->direction;
        $originalType = $secureAccess->type;
        $originalLabel = $secureAccess->label;
        $originalSubmittedAt = $secureAccess->submitted_at->copy();
        $originalViewedAt = $secureAccess->viewed_at->copy();

        $this
            ->actingAs($admin)
            ->patch(
                route(
                    'admin.tickets.secure-access.close',
                    [$ticket, $secureAccess]
                )
            )
            ->assertRedirect();

        $secureAccess->refresh();

        $this->assertSame($originalId, $secureAccess->id);
        $this->assertSame(
            $originalTicketId,
            $secureAccess->ticket_id
        );
        $this->assertSame(
            $originalCreatedBy,
            $secureAccess->created_by
        );
        $this->assertSame(
            $originalDirection,
            $secureAccess->direction
        );
        $this->assertSame(
            $originalType,
            $secureAccess->type
        );
        $this->assertSame(
            $originalLabel,
            $secureAccess->label
        );

        $this->assertTrue(
            $secureAccess->submitted_at->equalTo($originalSubmittedAt)
        );

        $this->assertTrue(
            $secureAccess->viewed_at->equalTo($originalViewedAt)
        );

        $this->assertNotNull($secureAccess->closed_at);
    }

    public function test_admin_can_close_requested_access_and_purge_it(): void
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

        $this
            ->actingAs($admin)
            ->patch(
                route(
                    'admin.tickets.secure-access.close',
                    [$ticket, $secureAccess]
                )
            )
            ->assertRedirect();

        $secureAccess->refresh();

        $this->assertSame(
            TicketSecureAccessStatus::Closed,
            $secureAccess->status
        );

        $this->assertNotNull($secureAccess->closed_at);
        $this->assertNull($secureAccess->secret);
    }

    public function test_customer_cannot_close_secure_access(): void
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
            ->patch(
                route(
                    'admin.tickets.secure-access.close',
                    [$ticket, $secureAccess]
                )
            );

        $response->assertForbidden();

        $secureAccess->refresh();

        $this->assertSame(
            TicketSecureAccessStatus::Submitted,
            $secureAccess->status
        );

        $this->assertSame(
            'temporary-password-123',
            $secureAccess->secret
        );

        $this->assertNull($secureAccess->closed_at);
    }

    public function test_secure_access_from_different_ticket_cannot_be_closed(): void
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
            ->patch(
                route(
                    'admin.tickets.secure-access.close',
                    [$secondTicket, $secureAccess]
                )
            );

        $response->assertNotFound();

        $secureAccess->refresh();

        $this->assertSame(
            TicketSecureAccessStatus::Submitted,
            $secureAccess->status
        );

        $this->assertSame(
            'temporary-password-123',
            $secureAccess->secret
        );

        $this->assertNull($secureAccess->closed_at);
    }

    public function test_closed_secure_access_cannot_be_closed_again(): void
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
            'closed_at' => now()->subMinutes(10),
        ]);

        $response = $this
            ->actingAs($admin)
            ->patch(
                route(
                    'admin.tickets.secure-access.close',
                    [$ticket, $secureAccess]
                )
            );

        $response->assertUnprocessable();
    }

    public function test_close_does_not_change_ticket_status(): void
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
            ->patch(
                route(
                    'admin.tickets.secure-access.close',
                    [$ticket, $secureAccess]
                )
            )
            ->assertRedirect();

        $ticket->refresh();

        $this->assertSame(
            $originalStatus,
            $ticket->status
        );
    }
}