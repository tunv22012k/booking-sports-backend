<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = \App\Models\User::where('role', 'customer')->first()
            ?? \App\Models\User::first();

        if (!$user) {
            return;
        }

        $bookings = [
            [
                'venueName' => 'Sân Cầu Lông Quân Khu 5',
                'courtName' => 'Sân 1',
                'date' => now()->addDays(2)->toDateString(),
                'start_time' => '18:00',
                'end_time' => '19:00',
                'price' => 100000,
                'status' => 'confirmed',
                'is_for_transfer' => false,
            ],
            [
                'venueName' => 'Sân Bóng Đá Tuyên Sơn',
                'courtName' => 'Sân 1',
                'date' => now()->addDays(5)->toDateString(),
                'start_time' => '17:00',
                'end_time' => '18:30',
                'price' => 300000,
                'status' => 'pending',
                'is_for_transfer' => false,
            ],
            [
                'venueName' => 'Sân Tennis Công Viên 29/3',
                'courtName' => 'Sân 1',
                'date' => now()->subDays(5)->toDateString(),
                'start_time' => '06:00',
                'end_time' => '07:00',
                'price' => 150000,
                'status' => 'completed',
                'is_for_transfer' => false,
            ],
        ];

        foreach ($bookings as $b) {
            $venue = \App\Models\Venue::where('name', $b['venueName'])->first();
            if (!$venue) {
                continue;
            }

            $court = $venue->courts()->where('name', $b['courtName'])->first();
            if (!$court) {
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
                'is_for_transfer' => $b['is_for_transfer'],
            ]);
        }
    }
}
