<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bookings = [
            [
                'venueName' => "Sân Cầu Lông Quân Khu 5",
                'courtName' => "Sân 1",
                'date' => "2026-01-21",
                'start_time' => "18:00",
                'end_time' => "19:00",
                'price' => 100000,
                'status' => "confirmed",
                'is_for_transfer' => false
            ],
            [
                'venueName' => "Sân Bóng Đá Tuyên Sơn",
                'courtName' => "Sân A",
                'date' => "2026-01-25",
                'start_time' => "17:00",
                'end_time' => "18:30",
                'price' => 300000,
                'status' => "pending",
                'is_for_transfer' => false
            ],
            [
                'venueName' => "Sân Tennis Công Viên 29/3",
                'courtName' => "Sân Chính",
                'date' => "2026-01-10",
                'start_time' => "06:00",
                'end_time' => "07:00",
                'price' => 150000,
                'status' => "completed",
                'is_for_transfer' => false
            ],
            [
                'venueName' => "Sân Cầu Lông Chi Lăng",
                'courtName' => "Sân 3",
                'date' => "2026-01-28",
                'start_time' => "19:00",
                'end_time' => "20:00",
                'price' => 120000,
                'status' => "confirmed",
                'is_for_transfer' => true,
                'transferStatus' => 'available',
                'transferNote' => "Mình bận đột xuất, cần để lại sân. Giá gốc!",
                'transferPrice' => 120000
            ]
        ];

        $user = \App\Models\User::first() ?? \App\Models\User::factory()->create([
             'name' => 'Demo User',
             'email' => 'demo@example.com',
             'password' => bcrypt('password'),
        ]);

        foreach ($bookings as $b) {
            $venue = \App\Models\Venue::where('name', $b['venueName'])->first();
            if (!$venue) continue;

            $court = $venue->courts()->where('name', $b['courtName'])->first();
            if (!$court) {
                // Should have been created by VenueSeeder logice
                continue; 
            }

            \App\Models\Booking::create([
                'user_id' => $user->id,
                'court_id' => $court->id,
                'date' => $b['date'],
                'start_time' => $b['start_time'],
                'end_time' => $b['end_time'],
                'total_price' => $b['price'],
                'status' => $b['status'],
                'is_paid' => $b['status'] === 'confirmed' || $b['status'] === 'completed',
                
                'is_for_transfer' => $b['is_for_transfer'] ?? false,
                'transfer_status' => $b['transferStatus'] ?? null,
                'transfer_note' => $b['transferNote'] ?? null,
                'transfer_price' => $b['transferPrice'] ?? null,
            ]);
        }
    }
}
