<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin role if it doesn't exist
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            [
                'uuid' => Str::uuid(),
                'guard_name' => 'sanctum',
            ]
        );
        
        // Create Administrator role if it doesn't exist
        $administratorRole = Role::firstOrCreate(
            ['name' => 'Administrator'],
            [
                'uuid' => Str::uuid(),
                'guard_name' => 'sanctum',
            ]
        );
        
        // Create User role if it doesn't exist
        $userRole = Role::firstOrCreate(
            ['name' => 'User'],
            [
                'uuid' => Str::uuid(),
                'guard_name' => 'sanctum',
            ]
        );
        
        // Create Manager role if it doesn't exist
        $managerRole = Role::firstOrCreate(
            ['name' => 'Manager'],
            [
                'uuid' => Str::uuid(),
                'guard_name' => 'sanctum',
            ]
        );
        
        $this->command->info('Roles created or updated successfully:');
        $this->command->info('- admin');
        $this->command->info('- Administrator');
        $this->command->info('- User');
        $this->command->info('- Manager');
    }
}