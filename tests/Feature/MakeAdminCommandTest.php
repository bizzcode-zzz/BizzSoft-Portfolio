<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MakeAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_command_creates_new_admin(): void
    {
        $this->artisan('bizzsoft:make-admin', [
            'email' => 'admin@bizzsoft.test',
            '--name' => 'BizzSoft Admin',
        ])
            ->expectsQuestion(
                'Administrator password',
                'SecurePassword123!'
            )
            ->expectsQuestion(
                'Confirm administrator password',
                'SecurePassword123!'
            )
            ->expectsOutput('Administrator created successfully.')
            ->assertSuccessful();

        $user = User::query()
            ->where('email', 'admin@bizzsoft.test')
            ->first();

        $this->assertNotNull($user);
        $this->assertSame('BizzSoft Admin', $user->name);
        $this->assertTrue($user->hasRole('admin'));
    }

    public function test_admin_password_is_stored_hashed(): void
    {
        $password = 'SecurePassword123!';

        $this->artisan('bizzsoft:make-admin', [
            'email' => 'admin@bizzsoft.test',
            '--name' => 'BizzSoft Admin',
        ])
            ->expectsQuestion(
                'Administrator password',
                $password
            )
            ->expectsQuestion(
                'Confirm administrator password',
                $password
            )
            ->assertSuccessful();

        $user = User::query()
            ->where('email', 'admin@bizzsoft.test')
            ->firstOrFail();

        $this->assertNotSame($password, $user->password);
        $this->assertTrue(
            Hash::check($password, $user->password)
        );
    }

    public function test_command_rejects_mismatched_password_confirmation(): void
    {
        $this->artisan('bizzsoft:make-admin', [
            'email' => 'admin@bizzsoft.test',
            '--name' => 'BizzSoft Admin',
        ])
            ->expectsQuestion(
                'Administrator password',
                'SecurePassword123!'
            )
            ->expectsQuestion(
                'Confirm administrator password',
                'DifferentPassword123!'
            )
            ->assertFailed();

        $this->assertDatabaseMissing('users', [
            'email' => 'admin@bizzsoft.test',
        ]);
    }

    public function test_command_does_not_recreate_existing_admin(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@bizzsoft.test',
        ]);

        $admin->assignRole('admin');

        $this->artisan('bizzsoft:make-admin', [
            'email' => 'admin@bizzsoft.test',
        ])
            ->expectsOutput('This user is already an administrator.')
            ->assertFailed();

        $this->assertSame(
            1,
            User::query()
                ->where('email', 'admin@bizzsoft.test')
                ->count()
        );

        $this->assertTrue(
            $admin->fresh()->hasRole('admin')
        );
    }

    public function test_existing_customer_can_be_promoted_to_admin_after_confirmation(): void
    {
        $customer = User::factory()->create([
            'email' => 'customer@bizzsoft.test',
        ]);

        $customer->assignRole('customer');

        $this->artisan('bizzsoft:make-admin', [
            'email' => 'customer@bizzsoft.test',
        ])
            ->expectsConfirmation(
                'The user customer@bizzsoft.test already exists. Promote this user to administrator?',
                'yes'
            )
            ->expectsOutput('Administrator role assigned successfully.')
            ->assertSuccessful();

        $customer->refresh();

        $this->assertTrue($customer->hasRole('admin'));
        $this->assertFalse($customer->hasRole('customer'));
    }

    public function test_existing_customer_is_not_promoted_without_confirmation(): void
    {
        $customer = User::factory()->create([
            'email' => 'customer@bizzsoft.test',
        ]);

        $customer->assignRole('customer');

        $this->artisan('bizzsoft:make-admin', [
            'email' => 'customer@bizzsoft.test',
        ])
            ->expectsConfirmation(
                'The user customer@bizzsoft.test already exists. Promote this user to administrator?',
                'no'
            )
            ->expectsOutput('No changes were made.')
            ->assertSuccessful();

        $customer->refresh();

        $this->assertTrue($customer->hasRole('customer'));
        $this->assertFalse($customer->hasRole('admin'));
    }
}