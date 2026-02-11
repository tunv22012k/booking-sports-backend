<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Venue;
use App\Models\Court;
use App\Models\CourtSchedule;
use App\Models\OwnerExtra;
use App\Models\VenueAmenity;
use App\Models\Booking;

class MarketplaceSeeder extends Seeder
{
    public function run(): void
    {
        // ── Owner user ──
        $owner = User::create([
            'name' => 'Chủ sân Demo',
            'email' => 'owner@example.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'phone' => '0901234567',
        ]);

        // ── Customer user ──
        $customer = User::create([
            'name' => 'Người đặt Demo',
            'email' => 'customer@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        // ── Owner-level extras catalog (shared across all venues/courts) ──
        $extraRacket = OwnerExtra::create(['user_id' => $owner->id, 'name' => 'Thuê vợt', 'price' => 30000]);
        $extraShuttle = OwnerExtra::create(['user_id' => $owner->id, 'name' => 'Mua cầu (1 hộp)', 'price' => 50000]);
        $extraDrink = OwnerExtra::create(['user_id' => $owner->id, 'name' => 'Nước uống', 'price' => 10000]);
        $extraTowel = OwnerExtra::create(['user_id' => $owner->id, 'name' => 'Khăn lau', 'price' => 5000]);
        $extraTennisRacket = OwnerExtra::create(['user_id' => $owner->id, 'name' => 'Thuê vợt tennis', 'price' => 50000]);
        $extraTennisBall = OwnerExtra::create(['user_id' => $owner->id, 'name' => 'Bóng tennis (3 quả)', 'price' => 40000]);

        // ── Venue 1: Sân cầu lông ──
        $venue1 = Venue::create([
            'owner_id' => $owner->id,
            'name' => 'Sân Cầu Lông Hải Châu',
            'type' => 'badminton',
            'description' => 'Sân cầu lông chất lượng cao tại trung tâm Hải Châu, Đà Nẵng.',
            'address' => '123 Nguyễn Văn Linh, Hải Châu, Đà Nẵng',
            'lat' => 16.0544,
            'lng' => 108.2022,
            'phone' => '0901234567',
            'email' => 'badminton@example.com',
            'operating_hours' => '06:00-22:00',
            'image' => 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=800',
            'rating' => 4.5,
            'total_reviews' => 12,
        ]);

        foreach (['WiFi miễn phí', 'Bãi đỗ xe', 'Phòng thay đồ', 'Căng tin', 'Nước uống'] as $amenity) {
            VenueAmenity::create(['venue_id' => $venue1->id, 'name' => $amenity]);
        }

        // Court A — uses racket, shuttle, drink from owner catalog
        $courtA = Court::create([
            'venue_id' => $venue1->id,
            'name' => 'Sân A',
            'description' => 'Sân chính, sàn gỗ cao cấp',
        ]);
        $courtA->extras()->sync([$extraRacket->id, $extraShuttle->id, $extraDrink->id]);

        // Court B — uses racket, drink, towel
        $courtB = Court::create([
            'venue_id' => $venue1->id,
            'name' => 'Sân B',
            'description' => 'Sân phụ, sàn nhựa PVC',
        ]);
        $courtB->extras()->sync([$extraRacket->id, $extraDrink->id, $extraTowel->id]);

        // Schedules for Court A (Mon-Fri)
        for ($day = 1; $day <= 5; $day++) {
            CourtSchedule::create(['court_id' => $courtA->id, 'day_of_week' => $day, 'start_time' => '06:00', 'end_time' => '09:00', 'price' => 80000, 'effective_from' => '2026-01-01']);
            CourtSchedule::create(['court_id' => $courtA->id, 'day_of_week' => $day, 'start_time' => '09:00', 'end_time' => '17:00', 'price' => 100000, 'effective_from' => '2026-01-01']);
            CourtSchedule::create(['court_id' => $courtA->id, 'day_of_week' => $day, 'start_time' => '17:00', 'end_time' => '22:00', 'price' => 150000, 'effective_from' => '2026-01-01']);
        }
        foreach ([6, 0] as $day) {
            CourtSchedule::create(['court_id' => $courtA->id, 'day_of_week' => $day, 'start_time' => '06:00', 'end_time' => '12:00', 'price' => 120000, 'effective_from' => '2026-01-01']);
            CourtSchedule::create(['court_id' => $courtA->id, 'day_of_week' => $day, 'start_time' => '12:00', 'end_time' => '22:00', 'price' => 180000, 'effective_from' => '2026-01-01']);
        }

        // Schedules for Court B
        for ($day = 0; $day <= 6; $day++) {
            CourtSchedule::create(['court_id' => $courtB->id, 'day_of_week' => $day, 'start_time' => '06:00', 'end_time' => '12:00', 'price' => 60000, 'effective_from' => '2026-01-01']);
            CourtSchedule::create(['court_id' => $courtB->id, 'day_of_week' => $day, 'start_time' => '12:00', 'end_time' => '22:00', 'price' => 100000, 'effective_from' => '2026-01-01']);
        }

        // ── Venue 2: Tổ hợp thể thao — same owner, same extras catalog ──
        $venue2 = Venue::create([
            'owner_id' => $owner->id,
            'name' => 'Tổ Hợp Thể Thao Sơn Trà',
            'type' => 'complex',
            'description' => 'Khu tổ hợp thể thao đa năng.',
            'address' => '456 Ngô Quyền, Sơn Trà, Đà Nẵng',
            'lat' => 16.0678,
            'lng' => 108.2350,
            'phone' => '0909876543',
            'email' => 'complex@example.com',
            'operating_hours' => '05:30-23:00',
            'image' => 'https://images.unsplash.com/photo-1558618666-fcd25c85f82e?w=800',
            'rating' => 4.2,
            'total_reviews' => 8,
        ]);

        foreach (['WiFi miễn phí', 'Bãi đỗ xe rộng', 'Phòng tắm', 'Căng tin'] as $amenity) {
            VenueAmenity::create(['venue_id' => $venue2->id, 'name' => $amenity]);
        }

        // Tennis court — uses tennis racket, tennis ball, drink from SAME owner catalog
        $courtTennis = Court::create([
            'venue_id' => $venue2->id,
            'name' => 'Sân Tennis 1',
            'type' => 'tennis',
            'description' => 'Sân tennis mặt cứng chuẩn quốc tế',
        ]);
        $courtTennis->extras()->sync([$extraTennisRacket->id, $extraTennisBall->id, $extraDrink->id]);

        for ($day = 0; $day <= 6; $day++) {
            CourtSchedule::create(['court_id' => $courtTennis->id, 'day_of_week' => $day, 'start_time' => '06:00', 'end_time' => '10:00', 'price' => 150000, 'effective_from' => '2026-01-01']);
            CourtSchedule::create(['court_id' => $courtTennis->id, 'day_of_week' => $day, 'start_time' => '10:00', 'end_time' => '17:00', 'price' => 200000, 'effective_from' => '2026-01-01']);
            CourtSchedule::create(['court_id' => $courtTennis->id, 'day_of_week' => $day, 'start_time' => '17:00', 'end_time' => '23:00', 'price' => 250000, 'effective_from' => '2026-01-01']);
        }

        // ── Sample bookings ──
        for ($i = 1; $i <= 3; $i++) {
            Booking::create([
                'user_id' => $customer->id,
                'court_id' => $courtA->id,
                'date' => now()->addDays($i)->toDateString(),
                'start_time' => '18:00',
                'end_time' => '19:00',
                'total_price' => 150000,
                'status' => 'confirmed',
                'is_paid' => true,
                'is_for_transfer' => true,
                'transfer_status' => 'available',
                'transfer_price' => 120000 - ($i * 5000),
                'transfer_note' => 'Bận việc đột xuất, pass rẻ cho anh em',
            ]);
        }
    }
}
