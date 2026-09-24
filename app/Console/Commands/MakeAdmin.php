<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class MakeAdmin extends Command
{
    protected $signature = 'bizzsoft:make-admin
                            {email : Email address of the administrator}
                            {--name= : Name of the administrator}';

    protected $description = 'Create a new BizzSoft administrator or promote an existing user';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));

        $existingUser = User::query()
            ->where('email', $email)
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Existing User
        |--------------------------------------------------------------------------
        */

        if ($existingUser) {
            if ($existingUser->hasRole('admin')) {
                $this->error('This user is already an administrator.');

                return self::FAILURE;
            }

            if (! $this->confirm(
                "The user {$existingUser->email} already exists. Promote this user to administrator?"
            )) {
                $this->info('No changes were made.');

                return self::SUCCESS;
            }

            $existingUser->syncRoles(['admin']);

            $this->newLine();
            $this->info('Administrator role assigned successfully.');
            $this->line("Name: {$existingUser->name}");
            $this->line("Email: {$existingUser->email}");

            return self::SUCCESS;
        }

        /*
        |--------------------------------------------------------------------------
        | New Administrator
        |--------------------------------------------------------------------------
        */

        $name = $this->option('name');

        if (! $name) {
            $name = $this->ask('Administrator name');
        }

        $password = $this->secret('Administrator password');
        $passwordConfirmation = $this->secret('Confirm administrator password');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'confirmed',
                Password::defaults(),
            ],
        ]);

        if ($validator->fails()) {
            $this->newLine();

            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        $user->assignRole('admin');

        $this->newLine();
        $this->info('Administrator created successfully.');
        $this->line("Name: {$user->name}");
        $this->line("Email: {$user->email}");

        return self::SUCCESS;
    }
}