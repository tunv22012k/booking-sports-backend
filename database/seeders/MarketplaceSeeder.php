<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MarketplaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure we have a user
        $user = \App\Models\User::first();
        if (!$user) {
            $user = \App\Models\User::create([
                'name' => 'Demo Seller',
                'email' => 'seller@example.com',
                'password' => bcrypt('password'),
            ]);
        }

        // Ensure we have a court
        $court = \App\Models\Court::first();
        if (!$court) {
            // Need a venue first if no court
            $venue = \App\Models\Venue::create([
                'name' => 'Sân demo',
                'type' => 'football', // Corrected typo
                'address' => '123 Test Street',
                'lat' => 16.0,
                'lng' => 108.0,
                'price' => 100000
            ]);
            $court = \App\Models\Court::create([
                'venue_id' => $venue->id,
                'name' => 'Sân 1',
                'prices' => []
            ]);
        }

        // Create 3 listings
        for ($i = 1; $i <= 3; $i++) {
            \App\Models\Booking::create([
                'user_id' => $user->id,
                'court_id' => $court->id,
                'date' => now()->addDays($i)->toDateString(),
                'start_time' => '18:00',
                'end_time' => '19:00',
                'total_price' => 100000,
                'status' => 'confirmed',
                'is_paid' => true,
                'is_for_transfer' => true,
                'transfer_status' => 'available',
                'transfer_price' => 80000 - ($i * 5000), // Discounted
                'transfer_note' => 'Bận việc đột xuất, pass rẻ cho anh em',
            ]);
        }
    }
}
