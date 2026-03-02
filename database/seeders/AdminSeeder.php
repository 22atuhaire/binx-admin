<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     *
     * Creates the first admin user for system management.
     * This admin can log into the admin site, approve collectors,
     * and monitor system activity.
     */
    public function run(): void
    {
        // Check if admin already exists
        if (User::where('email', 'admin@binx.com')->exists()) {
            $this->command->warn('Admin user already exists!');

            return;
        }

        // Create the first admin user
        $admin = User::create([
            'name' => 'System Administrator',
            'email' => 'admin@binx.com',
            'password' => Hash::make('admin123'), // Change this password after first login!
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE, // Admins are always active
            'email_verified_at' => now(),
        ]);

        $this->command->info('✓ Admin user created successfully!');
        $this->command->info('  Email: admin@binx.com');
        $this->command->warn('  Password: admin123 (CHANGE THIS IMMEDIATELY!)');
    }
}
