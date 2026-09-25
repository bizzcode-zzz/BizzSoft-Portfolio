<?php

namespace Tests\Feature;

use App\Enums\TicketSecureAccessDirection;
use App\Enums\TicketSecureAccessStatus;
use App\Enums\TicketSecureAccessType;
use App\Models\Ticket;
use App\Models\TicketSecureAccess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TicketSecureAccessFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_has_secure_access_relationship(): void
    {
        $ticket = Ticket::factory()->create();

        $secureAccess = TicketSecureAccess::factory()->create([
            'ticket_id' => $ticket->id,
        ]);

        $this->assertTrue(
            $ticket->secureAccesses->contains($secureAccess)
        );
    }

    public function test_secure_access_belongs_to_ticket_and_creator(): void
    {
        $ticket = Ticket::factory()->create();
        $creator = User::factory()->create();

        $secureAccess = TicketSecureAccess::factory()->create([
            'ticket_id' => $ticket->id,
            'created_by' => $creator->id,
        ]);

        $this->assertTrue($secureAccess->ticket->is($ticket));
        $this->assertTrue($secureAccess->creator->is($creator));
    }

    public function test_secure_access_uses_expected_enum_casts(): void
    {
        $secureAccess = TicketSecureAccess::factory()->create([
            'direction' => TicketSecureAccessDirection::CustomerToAdmin,
            'type' => TicketSecureAccessType::ApplicationLogin,
            'status' => TicketSecureAccessStatus::Submitted,
        ]);

        $this->assertSame(
            TicketSecureAccessDirection::CustomerToAdmin,
            $secureAccess->direction
        );

        $this->assertSame(
            TicketSecureAccessType::ApplicationLogin,
            $secureAccess->type
        );

        $this->assertSame(
            TicketSecureAccessStatus::Submitted,
            $secureAccess->status
        );
    }

    public function test_sensitive_values_are_encrypted_at_rest(): void
    {
        $secureAccess = TicketSecureAccess::factory()->create([
            'login_url' => 'https://support-example.test/login',
            'username' => 'support-user',
            'secret' => 'temporary-password-123',
            'notes' => 'Temporary access for support only.',
        ]);

        $raw = DB::table('ticket_secure_accesses')
            ->where('id', $secureAccess->id)
            ->first();

        $this->assertNotSame(
            'https://support-example.test/login',
            $raw->login_url
        );

        $this->assertNotSame(
            'support-user',
            $raw->username
        );

        $this->assertNotSame(
            'temporary-password-123',
            $raw->secret
        );

        $this->assertNotSame(
            'Temporary access for support only.',
            $raw->notes
        );

        $secureAccess->refresh();

        $this->assertSame(
            'https://support-example.test/login',
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
            'Temporary access for support only.',
            $secureAccess->notes
        );
    }

    public function test_secure_access_supports_requested_state_without_credentials(): void
    {
        $secureAccess = TicketSecureAccess::factory()->create([
            'direction' => TicketSecureAccessDirection::CustomerToAdmin,
            'type' => TicketSecureAccessType::ApplicationLogin,
            'label' => 'Inventory Script Admin Access',
            'login_url' => null,
            'username' => null,
            'secret' => null,
            'notes' => null,
            'status' => TicketSecureAccessStatus::Requested,
            'submitted_at' => null,
            'viewed_at' => null,
            'closed_at' => null,
        ]);

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
}