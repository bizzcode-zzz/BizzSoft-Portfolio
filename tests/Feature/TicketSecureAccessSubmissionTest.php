<?php

namespace Tests\Feature;

use App\Enums\TicketSecureAccessDirection;
use App\Enums\TicketSecureAccessStatus;
use App\Models\Ticket;
use App\Models\TicketSecureAccess;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TicketSecureAccessSubmissionTest extends TestCase
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

    private function makeRequestedAccess(
        Ticket $ticket,
        User $admin
    ): TicketSecureAccess {
        return TicketSecureAccess::factory()->create([
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
    }

    public function test_ticket_owner_can_submit_requested_secure_access(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();

        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        $secureAccess = $this->makeRequestedAccess(
            $ticket,
            $admin
        );

        $response = $this
            ->actingAs($customer)
            ->post(
                route(
                    'tickets.secure-access.submit',
                    [$ticket, $secureAccess]
                ),
                [
                    'login_url' => 'https://example.test/admin',
                    'username' => 'support-user',
                    'secret' => 'temporary-password-123',
                    'notes' => 'Temporary access for this support ticket.',
                ]
            );

        $response->assertRedirect();

        $secureAccess->refresh();

        $this->assertSame(
            TicketSecureAccessStatus::Submitted,
            $secureAccess->status
        );

        $this->assertSame(
            'https://example.test/admin',
            $secureAccess->login_url
        );

        $this->assertSame(
            'support-user',
            $secureAccess->username
        );

        $this->assertSame(
            'temporary-password-123',
            $secureAccess->secret
        );

        $this->assertSame(
            'Temporary access for this support ticket.',
            $secureAccess->notes
        );

        $this->assertNotNull($secureAccess->submitted_at);
        $this->assertNull($secureAccess->viewed_at);
        $this->assertNull($secureAccess->closed_at);
    }

    public function test_submitted_credentials_are_encrypted_at_rest(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();

        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        $secureAccess = $this->makeRequestedAccess(
            $ticket,
            $admin
        );

        $this
            ->actingAs($customer)
            ->post(
                route(
                    'tickets.secure-access.submit',
                    [$ticket, $secureAccess]
                ),
                [
                    'login_url' => 'https://example.test/login',
                    'username' => 'ticket-user',
                    'secret' => 'ticket-secret-456',
                    'notes' => 'Support-only credentials.',
                ]
            )
            ->assertRedirect();

        $raw = DB::table('ticket_secure_accesses')
            ->where('id', $secureAccess->id)
            ->first();

        $this->assertNotSame(
            'https://example.test/login',
            $raw->login_url
        );

        $this->assertNotSame(
            'ticket-user',
            $raw->username
        );

        $this->assertNotSame(
            'ticket-secret-456',
            $raw->secret
        );

        $this->assertNotSame(
            'Support-only credentials.',
            $raw->notes
        );
    }

    public function test_other_customer_cannot_submit_secure_access(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeCustomer();
        $otherCustomer = $this->makeCustomer();

        $ticket = Ticket::factory()->create([
            'user_id' => $owner->id,
        ]);

        $secureAccess = $this->makeRequestedAccess(
            $ticket,
            $admin
        );

        $response = $this
            ->actingAs($otherCustomer)
            ->post(
                route(
                    'tickets.secure-access.submit',
                    [$ticket, $secureAccess]
                ),
                [
                    'secret' => 'should-not-be-saved',
                ]
            );

        $response->assertForbidden();

        $secureAccess->refresh();

        $this->assertSame(
            TicketSecureAccessStatus::Requested,
            $secureAccess->status
        );

        $this->assertNull($secureAccess->secret);
        $this->assertNull($secureAccess->submitted_at);
    }

    public function test_customer_cannot_submit_secure_access_for_different_ticket(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();

        $firstTicket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        $secondTicket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        $secureAccess = $this->makeRequestedAccess(
            $firstTicket,
            $admin
        );

        $response = $this
            ->actingAs($customer)
            ->post(
                route(
                    'tickets.secure-access.submit',
                    [$secondTicket, $secureAccess]
                ),
                [
                    'secret' => 'should-not-be-saved',
                ]
            );

        $response->assertNotFound();

        $secureAccess->refresh();

        $this->assertSame(
            TicketSecureAccessStatus::Requested,
            $secureAccess->status
        );

        $this->assertNull($secureAccess->secret);
    }

    public function test_customer_cannot_resubmit_already_submitted_access(): void
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
            'status' => TicketSecureAccessStatus::Submitted,
            'secret' => 'original-secret',
            'submitted_at' => now(),
        ]);

        $response = $this
            ->actingAs($customer)
            ->post(
                route(
                    'tickets.secure-access.submit',
                    [$ticket, $secureAccess]
                ),
                [
                    'secret' => 'replacement-secret',
                ]
            );

        $response->assertUnprocessable();

        $secureAccess->refresh();

        $this->assertSame(
            'original-secret',
            $secureAccess->secret
        );
    }

    public function test_secret_is_required_when_customer_submits_access(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();

        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        $secureAccess = $this->makeRequestedAccess(
            $ticket,
            $admin
        );

        $response = $this
            ->actingAs($customer)
            ->from(route('tickets.show', $ticket))
            ->post(
                route(
                    'tickets.secure-access.submit',
                    [$ticket, $secureAccess]
                ),
                [
                    'login_url' => 'https://example.test/login',
                    'username' => 'support-user',
                    'secret' => '',
                ]
            );

        $response->assertRedirect(
            route('tickets.show', $ticket)
        );

        $response->assertSessionHasErrors('secret');

        $secureAccess->refresh();

        $this->assertSame(
            TicketSecureAccessStatus::Requested,
            $secureAccess->status
        );

        $this->assertNull($secureAccess->submitted_at);
    }

    public function test_submission_does_not_change_ticket_status(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();

        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        $originalStatus = $ticket->status;

        $secureAccess = $this->makeRequestedAccess(
            $ticket,
            $admin
        );

        $this
            ->actingAs($customer)
            ->post(
                route(
                    'tickets.secure-access.submit',
                    [$ticket, $secureAccess]
                ),
                [
                    'secret' => 'temporary-secret',
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