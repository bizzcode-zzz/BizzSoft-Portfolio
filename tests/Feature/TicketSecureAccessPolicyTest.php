<?php

namespace Tests\Feature;

use App\Enums\TicketSecureAccessDirection;
use App\Models\Ticket;
use App\Models\TicketSecureAccess;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketSecureAccessPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function makeCustomer(): User
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        return $user;
    }

    private function makeAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function makeTicketFor(User $customer): Ticket
    {
        return Ticket::factory()->create([
            'user_id' => $customer->id,
        ]);
    }

    public function test_admin_can_view_any_ticket_secure_access_record(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();
        $ticket = $this->makeTicketFor($customer);

        $secureAccess = TicketSecureAccess::factory()->create([
            'ticket_id' => $ticket->id,
        ]);

        $this->assertTrue(
            $admin->can('view', $secureAccess)
        );
    }

    public function test_ticket_owner_can_view_secure_access_metadata(): void
    {
        $customer = $this->makeCustomer();
        $ticket = $this->makeTicketFor($customer);

        $secureAccess = TicketSecureAccess::factory()->create([
            'ticket_id' => $ticket->id,
        ]);

        $this->assertTrue(
            $customer->can('view', $secureAccess)
        );
    }

    public function test_other_customer_cannot_view_ticket_secure_access(): void
    {
        $owner = $this->makeCustomer();
        $otherCustomer = $this->makeCustomer();

        $ticket = $this->makeTicketFor($owner);

        $secureAccess = TicketSecureAccess::factory()->create([
            'ticket_id' => $ticket->id,
        ]);

        $this->assertFalse(
            $otherCustomer->can('view', $secureAccess)
        );
    }

    public function test_ticket_owner_can_submit_customer_to_admin_access(): void
    {
        $customer = $this->makeCustomer();
        $ticket = $this->makeTicketFor($customer);

        $secureAccess = TicketSecureAccess::factory()->create([
            'ticket_id' => $ticket->id,
            'direction' => TicketSecureAccessDirection::CustomerToAdmin,
        ]);

        $this->assertTrue(
            $customer->can('submit', $secureAccess)
        );
    }

    public function test_other_customer_cannot_submit_customer_to_admin_access(): void
    {
        $owner = $this->makeCustomer();
        $otherCustomer = $this->makeCustomer();

        $ticket = $this->makeTicketFor($owner);

        $secureAccess = TicketSecureAccess::factory()->create([
            'ticket_id' => $ticket->id,
            'direction' => TicketSecureAccessDirection::CustomerToAdmin,
        ]);

        $this->assertFalse(
            $otherCustomer->can('submit', $secureAccess)
        );
    }

    public function test_admin_cannot_use_customer_submission_permission_for_customer_to_admin_access(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();
        $ticket = $this->makeTicketFor($customer);

        $secureAccess = TicketSecureAccess::factory()->create([
            'ticket_id' => $ticket->id,
            'direction' => TicketSecureAccessDirection::CustomerToAdmin,
        ]);

        $this->assertFalse(
            $admin->can('submit', $secureAccess)
        );
    }

    public function test_admin_can_submit_admin_to_customer_access(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();
        $ticket = $this->makeTicketFor($customer);

        $secureAccess = TicketSecureAccess::factory()->create([
            'ticket_id' => $ticket->id,
            'direction' => TicketSecureAccessDirection::AdminToCustomer,
        ]);

        $this->assertTrue(
            $admin->can('submit', $secureAccess)
        );
    }

    public function test_customer_cannot_submit_admin_to_customer_access(): void
    {
        $customer = $this->makeCustomer();
        $ticket = $this->makeTicketFor($customer);

        $secureAccess = TicketSecureAccess::factory()->create([
            'ticket_id' => $ticket->id,
            'direction' => TicketSecureAccessDirection::AdminToCustomer,
        ]);

        $this->assertFalse(
            $customer->can('submit', $secureAccess)
        );
    }

    public function test_admin_can_reveal_customer_to_admin_access(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();
        $ticket = $this->makeTicketFor($customer);

        $secureAccess = TicketSecureAccess::factory()->create([
            'ticket_id' => $ticket->id,
            'direction' => TicketSecureAccessDirection::CustomerToAdmin,
        ]);

        $this->assertTrue(
            $admin->can('reveal', $secureAccess)
        );
    }

    public function test_customer_cannot_reveal_customer_to_admin_access(): void
    {
        $customer = $this->makeCustomer();
        $ticket = $this->makeTicketFor($customer);

        $secureAccess = TicketSecureAccess::factory()->create([
            'ticket_id' => $ticket->id,
            'direction' => TicketSecureAccessDirection::CustomerToAdmin,
        ]);

        $this->assertFalse(
            $customer->can('reveal', $secureAccess)
        );
    }

    public function test_ticket_owner_can_reveal_admin_to_customer_access(): void
    {
        $customer = $this->makeCustomer();
        $ticket = $this->makeTicketFor($customer);

        $secureAccess = TicketSecureAccess::factory()->create([
            'ticket_id' => $ticket->id,
            'direction' => TicketSecureAccessDirection::AdminToCustomer,
        ]);

        $this->assertTrue(
            $customer->can('reveal', $secureAccess)
        );
    }

    public function test_other_customer_cannot_reveal_admin_to_customer_access(): void
    {
        $owner = $this->makeCustomer();
        $otherCustomer = $this->makeCustomer();

        $ticket = $this->makeTicketFor($owner);

        $secureAccess = TicketSecureAccess::factory()->create([
            'ticket_id' => $ticket->id,
            'direction' => TicketSecureAccessDirection::AdminToCustomer,
        ]);

        $this->assertFalse(
            $otherCustomer->can('reveal', $secureAccess)
        );
    }

    public function test_only_admin_can_close_ticket_secure_access(): void
    {
        $admin = $this->makeAdmin();
        $customer = $this->makeCustomer();
        $ticket = $this->makeTicketFor($customer);

        $secureAccess = TicketSecureAccess::factory()->create([
            'ticket_id' => $ticket->id,
        ]);

        $this->assertTrue(
            $admin->can('close', $secureAccess)
        );

        $this->assertFalse(
            $customer->can('close', $secureAccess)
        );
    }
}