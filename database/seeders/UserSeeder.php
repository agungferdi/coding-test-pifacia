<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // First, ensure we have an admin role
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            [
                'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                'name' => 'admin',
                'guard_name' => 'sanctum'
            ]
        );

        // Create admin user
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('password123'),
                'role_id' => $adminRole->id
            ]
        );

        $this->command->info('Admin user created: admin@example.com / password123');
    }
}
