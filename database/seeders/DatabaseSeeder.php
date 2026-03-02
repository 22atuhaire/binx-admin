<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test admin
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        // Create test donor
        User::factory()->donor()->create([
            'name' => 'Test Donor',
            'email' => 'test@example.com',
        ]);

        // Create seeded data
        $this->call([
            DataSeeder::class,
        ]);
    }
}
