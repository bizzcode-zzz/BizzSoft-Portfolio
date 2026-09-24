<?php

namespace Tests\Feature\Authorization;

use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_customer_can_view_own_ticket(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customer)
            ->create();

        $this->assertTrue(
            $customer->can('view', $ticket)
        );
    }

    public function test_customer_cannot_view_another_customers_ticket(): void
    {
        $customerA = User::factory()->create();
        $customerA->assignRole('customer');

        $customerB = User::factory()->create();
        $customerB->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customerB)
            ->create();

        $this->assertFalse(
            $customerA->can('view', $ticket)
        );
    }

    public function test_admin_can_view_any_customers_ticket(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customer)
            ->create();

        $this->assertTrue(
            $admin->can('view', $ticket)
        );
    }

    public function test_customer_can_create_ticket(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $this->assertTrue(
            $customer->can('create', Ticket::class)
        );
    }

    public function test_admin_cannot_create_customer_ticket(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->assertFalse(
            $admin->can('create', Ticket::class)
        );
    }

    public function test_customer_can_update_own_ticket(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customer)
            ->create();

        $this->assertTrue(
            $customer->can('update', $ticket)
        );
    }

    public function test_customer_cannot_update_another_customers_ticket(): void
    {
        $customerA = User::factory()->create();
        $customerA->assignRole('customer');

        $customerB = User::factory()->create();
        $customerB->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customerB)
            ->create();

        $this->assertFalse(
            $customerA->can('update', $ticket)
        );
    }

    public function test_customer_cannot_delete_ticket(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customer)
            ->create();

        $this->assertFalse(
            $customer->can('delete', $ticket)
        );
    }

    public function test_admin_can_update_customer_ticket(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customer)
            ->create();

        $this->assertTrue(
            $admin->can('update', $ticket)
        );
    }

    public function test_admin_can_delete_customer_ticket(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customer)
            ->create();

        $this->assertTrue(
            $admin->can('delete', $ticket)
        );
    }

    public function test_unassigned_user_cannot_view_ticket(): void
    {
        $user = User::factory()->create();

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customer)
            ->create();

        $this->assertFalse(
            $user->can('view', $ticket)
        );
    }
}