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
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminTicketSecureAccessHandoffTest extends TestCase
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

    public function test_admin_can_send_secure_access_handoff_to_customer(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();

        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(
                route(
                    'admin.tickets.secure-access.handoff',
                    $ticket
                ),
                [
                    'type' => TicketSecureAccessType::ApplicationLogin->value,
                    'label' => 'Temporary Application Login',
                    'login_url' => 'https://example.test/login',
                    'username' => 'customer-user',
                    'secret' => 'temporary-password-789',
                    'notes' => 'Temporary login for testing.',
                ]
            );

        $response->assertRedirect();

        $secureAccess = TicketSecureAccess::query()->latest('id')->first();

        $this->assertNotNull($secureAccess);

        $this->assertSame(
            $ticket->id,
            $secureAccess->ticket_id
        );

        $this->assertSame(
            $admin->id,
            $secureAccess->created_by
        );

        $this->assertSame(
            TicketSecureAccessDirection::AdminToCustomer,
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

        $this->assertSame(
            'Temporary Application Login',
            $secureAccess->label
        );

        $this->assertSame(
            'https://example.test/login',
            $secureAccess->login_url
        );

        $this->assertSame(
            'customer-user',
            $secureAccess->username
        );

        $this->assertSame(
            'temporary-password-789',
            $secureAccess->secret
        );

        $this->assertSame(
            'Temporary login for testing.',
            $secureAccess->notes
        );

        $this->assertNotNull($secureAccess->submitted_at);
        $this->assertNull($secureAccess->viewed_at);
        $this->assertNull($secureAccess->closed_at);
    }

    public function test_handoff_credentials_are_encrypted_at_rest(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();

        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        $this
            ->actingAs($admin)
            ->post(
                route(
                    'admin.tickets.secure-access.handoff',
                    $ticket
                ),
                [
                    'type' => TicketSecureAccessType::ApplicationLogin->value,
                    'label' => 'Application Login',
                    'login_url' => 'https://example.test/login',
                    'username' => 'handoff-user',
                    'secret' => 'handoff-secret-123',
                    'notes' => 'Customer-only credentials.',
                ]
            )
            ->assertRedirect();

        $secureAccess = TicketSecureAccess::query()->latest('id')->first();

        $raw = DB::table('ticket_secure_accesses')
            ->where('id', $secureAccess->id)
            ->first();

        $this->assertNotSame(
            'https://example.test/login',
            $raw->login_url
        );

        $this->assertNotSame(
            'handoff-user',
            $raw->username
        );

        $this->assertNotSame(
            'handoff-secret-123',
            $raw->secret
        );

        $this->assertNotSame(
            'Customer-only credentials.',
            $raw->notes
        );
    }

    public function test_customer_cannot_use_admin_handoff_endpoint(): void
    {
        $customer = $this->makeCustomer();

        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        $response = $this
            ->actingAs($customer)
            ->post(
                route(
                    'admin.tickets.secure-access.handoff',
                    $ticket
                ),
                [
                    'type' => TicketSecureAccessType::ApplicationLogin->value,
                    'label' => 'Unauthorized Handoff',
                    'secret' => 'should-not-be-created',
                ]
            );

        $response->assertForbidden();

        $this->assertDatabaseCount(
            'ticket_secure_accesses',
            0
        );
    }

    public function test_guest_cannot_use_admin_handoff_endpoint(): void
    {
        $customer = $this->makeCustomer();

        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);

        $response = $this->post(
            route(
                'admin.tickets.secure-access.handoff',
                $ticket
            ),
            [
                'type' => TicketSecureAccessType::ApplicationLogin->value,
                'label' => 'Unauthorized Handoff',
                'secret' => 'should-not-be-created',
            ]
        );

        $response->assertRedirect(route('login'));

        $this->assertDatabaseCount(
            'ticket_secure_accesses',
            0
        );
    }

    public function test_handoff_requires_valid_secure_access_type(): void
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
                route(
                    'admin.tickets.secure-access.handoff',
                    $ticket
                ),
                [
                    'type' => 'invalid-type',
                    'label' => 'Invalid Type',
                    'secret' => 'temporary-secret',
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

    public function test_handoff_requires_label(): void
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
                route(
                    'admin.tickets.secure-access.handoff',
                    $ticket
                ),
                [
                    'type' => TicketSecureAccessType::ApplicationLogin->value,
                    'label' => '',
                    'secret' => 'temporary-secret',
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

    public function test_handoff_requires_secret(): void
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
                route(
                    'admin.tickets.secure-access.handoff',
                    $ticket
                ),
                [
                    'type' => TicketSecureAccessType::ApplicationLogin->value,
                    'label' => 'Application Login',
                    'secret' => '',
                ]
            );

        $response->assertRedirect(
            route('admin.tickets.show', $ticket)
        );

        $response->assertSessionHasErrors('secret');

        $this->assertDatabaseCount(
            'ticket_secure_accesses',
            0
        );
    }

    public function test_handoff_does_not_change_ticket_status(): void
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
                route(
                    'admin.tickets.secure-access.handoff',
                    $ticket
                ),
                [
                    'type' => TicketSecureAccessType::ApplicationLogin->value,
                    'label' => 'Temporary Login',
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