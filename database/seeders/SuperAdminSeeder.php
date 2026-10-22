<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SUPER_ADMIN_EMAIL', 'admin@barbersas.test');
        $password = env('SUPER_ADMIN_PASSWORD', 'password123');

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
                'password' => Hash::make($password),
                'role' => Role::SuperAdmin,
                'status' => 'active',
                'email_verified_at' => now(),
            ],
        );
    }
}
