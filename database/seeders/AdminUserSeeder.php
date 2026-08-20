<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@dole-bukidnon.gov.ph'],
            [
                'name' => 'System Administrator',
                'password' => bcrypt('admin123'),
                'employee_id' => 'ADMIN-001',
                'is_active' => true,
            ]
        );

        $admin->assignRole('Admin');
    }
}
