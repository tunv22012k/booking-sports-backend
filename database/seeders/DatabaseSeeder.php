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
        // User::factory(10)->create();
        
        // Ensure at least one user exists for Auth login
        if (!\App\Models\User::where('email', 'admin@example.com')->exists()) {
             \App\Models\User::factory()->create([
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => bcrypt('password'), // Or secure password
             ]);
        }

        $this->call([
            VenueSeeder::class,
            BookingSeeder::class,
        ]);
    }
}
