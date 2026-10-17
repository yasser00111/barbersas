<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * Creates or updates the initial Super Admin account.
 *
 * Super Admins are never created through the public registration form; they are
 * bootstrapped from the server by an operator who already has shell access.
 */
class BootstrapSuperAdmin extends Command
{
    protected $signature = 'barbersaas:super-admin
                            {email : Email address of the super admin}
                            {--name= : Full name}
                            {--password= : Password (omit to be prompted securely)}';

    protected $description = 'Bootstrap (create or update) a platform Super Admin account';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        $validator = Validator::make(['email' => $email], ['email' => ['required', 'email']]);

        if ($validator->fails()) {
            $this->error('Email tidak valid.');

            return self::FAILURE;
        }

        $name = $this->option('name') ?: $this->ask('Nama lengkap', 'Super Admin');
        $password = $this->option('password');

        if (! $password) {
            $password = $this->secret('Password (min 8 karakter)');
            $confirm = $this->secret('Ulangi password');

            if ($password !== $confirm) {
                $this->error('Password tidak sama.');

                return self::FAILURE;
            }
        }

        if (strlen((string) $password) < 8) {
            $this->error('Password minimal 8 karakter.');

            return self::FAILURE;
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'role' => Role::SuperAdmin,
                'status' => UserStatus::Active,
                'tenant_id' => null,
                'email_verified_at' => now(),
            ],
        );

        $this->info($user->wasRecentlyCreated
            ? "Super Admin dibuat: {$user->email}"
            : "Super Admin diperbarui: {$user->email}");

        return self::SUCCESS;
    }
}
