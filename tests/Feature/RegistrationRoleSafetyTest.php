<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegistrationRoleSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_main_database_seeder_initializes_required_roles(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('roles', [
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $this->assertDatabaseHas('roles', [
            'name' => 'customer',
            'guard_name' => 'web',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_successful_registration_assigns_customer_role(): void
    {
        Role::findOrCreate('customer', 'web');

        $this
            ->post(route('register'), [
                'name' => 'New Customer',
                'email' => 'customer@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ])
            ->assertRedirect(route('dashboard'));

        $user = User::query()
            ->where('email', 'customer@example.com')
            ->firstOrFail();

        $this->assertTrue(
            $user->hasRole('customer')
        );
    }

    public function test_registration_rolls_back_user_when_customer_role_assignment_fails(): void
    {
        $this->withoutExceptionHandling();

        try {
            $this->post(route('register'), [
                'name' => 'Rollback Customer',
                'email' => 'rollback@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

            $this->fail(
                'Registration unexpectedly succeeded without the customer role.'
            );
        } catch (RoleDoesNotExist) {
            $this->assertDatabaseMissing('users', [
                'email' => 'rollback@example.com',
            ]);
        }
    }
}
