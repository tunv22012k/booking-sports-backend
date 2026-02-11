<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Venue;
use App\Models\Court;
use App\Models\CourtSchedule;
use App\Models\VenueAmenity;

class VenueSeeder extends Seeder
{
    /**
     * Seed the application's database with venues.
     */
    public function run(): void
    {
        // Get or create an owner
        $owner = User::where('role', 'owner')->first();
        if (!$owner) {
            $owner = User::create([
                'name' => 'Default Owner',
                'email' => 'owner@default.com',
                'password' => bcrypt('password'),
                'role' => 'owner',
            ]);
        }

        // 1. Manual Da Nang venues
        $manualVenues = [
            [
                'name' => 'Sân Cầu Lông Quân Khu 5',
                'type' => 'badminton',
                'lat' => 16.0372,
                'lng' => 108.2120,
                'address' => '7 Duy Tân, Hòa Cường Bắc, Hải Châu, Đà Nẵng',
                'description' => 'Sân tiêu chuẩn thi đấu, thoáng mát, trung tâm thành phố.',
                'operating_hours' => '06:00-22:00',
                'base_price' => 80000,
            ],
            [
                'name' => 'Sân Bóng Đá Tuyên Sơn',
                'type' => 'football',
                'lat' => 16.0336,
                'lng' => 108.2238,
                'address' => 'Làng thể thao Tuyên Sơn, Hải Châu, Đà Nẵng',
                'description' => 'Cụm sân cỏ nhân tạo lớn nhất Đà Nẵng, dịch vụ tốt.',
                'operating_hours' => '05:30-22:30',
                'base_price' => 300000,
            ],
            [
                'name' => 'Sân Tennis Công Viên 29/3',
                'type' => 'tennis',
                'lat' => 16.0610,
                'lng' => 108.2045,
                'address' => 'Công viên 29/3, Thanh Khê, Đà Nẵng',
                'description' => 'Không gian xanh mát, yên tĩnh, mặt sân cứng.',
                'operating_hours' => '06:00-21:00',
                'base_price' => 150000,
            ],
        ];

        foreach ($manualVenues as $v) {
            if (Venue::where('name', $v['name'])->exists()) {
                continue;
            }

            $venue = Venue::create([
                'owner_id' => $owner->id,
                'name' => $v['name'],
                'type' => $v['type'],
                'lat' => $v['lat'],
                'lng' => $v['lng'],
                'address' => $v['address'],
                'description' => $v['description'],
                'operating_hours' => $v['operating_hours'],
                'rating' => 5.0,
                'coordinates' => \Illuminate\Support\Facades\DB::raw("ST_SetSRID(ST_MakePoint({$v['lng']}, {$v['lat']}), 4326)"),
            ]);

            // Add amenities
            foreach (['Bãi đỗ xe', 'Phòng thay đồ', 'Nước uống'] as $amenity) {
                VenueAmenity::create(['venue_id' => $venue->id, 'name' => $amenity]);
            }

            // Add 2 courts with schedules
            for ($c = 1; $c <= 2; $c++) {
                $court = Court::create([
                    'venue_id' => $venue->id,
                    'name' => "Sân $c",
                    'type' => $v['type'],
                ]);

                $this->createDefaultSchedules($court, $v['base_price']);
            }
        }

        // 2. Generated venues for other cities
        $cities = [
            'Ha Noi' => ['lat' => 21.0285, 'lng' => 105.8542],
            'Ho Chi Minh' => ['lat' => 10.8231, 'lng' => 106.6297],
            'Da Nang' => ['lat' => 16.0544, 'lng' => 108.2022],
            'Hai Phong' => ['lat' => 20.8449, 'lng' => 106.6881],
            'Can Tho' => ['lat' => 10.0452, 'lng' => 105.7469],
            'Nha Trang' => ['lat' => 12.2388, 'lng' => 109.1967],
            'Hue' => ['lat' => 16.4637, 'lng' => 107.5909],
            'Vung Tau' => ['lat' => 10.3460, 'lng' => 107.0843],
            'Da Lat' => ['lat' => 11.9404, 'lng' => 108.4583],
            'Ha Long' => ['lat' => 20.9499, 'lng' => 107.0733],
        ];

        $types = ['football', 'badminton', 'tennis', 'pickleball', 'basketball', 'swimming', 'gym'];

        foreach ($cities as $cityName => $coords) {
            for ($i = 1; $i <= 10; $i++) {
                $type = $types[array_rand($types)];
                $lat = $coords['lat'] + (mt_rand(-800, 800) / 10000);
                $lng = $coords['lng'] + (mt_rand(-800, 800) / 10000);
                $basePrice = mt_rand(5, 30) * 10000;

                $venue = Venue::create([
                    'owner_id' => $owner->id,
                    'name' => "Sân $type $cityName #$i",
                    'type' => $type,
                    'lat' => $lat,
                    'lng' => $lng,
                    'address' => "Địa chỉ ngẫu nhiên tại $cityName",
                    'description' => "Sân tập $type chất lượng cao tại $cityName.",
                    'operating_hours' => '06:00-22:00',
                    'rating' => mt_rand(35, 50) / 10,
                    'coordinates' => \Illuminate\Support\Facades\DB::raw("ST_SetSRID(ST_MakePoint($lng, $lat), 4326)"),
                ]);

                // Court + schedules
                $court = Court::create([
                    'venue_id' => $venue->id,
                    'name' => 'Sân 1',
                    'type' => $type,
                ]);
                $this->createDefaultSchedules($court, $basePrice);
            }
        }
    }

    /**
     * Create default schedules for all days of the week.
     */
    private function createDefaultSchedules(Court $court, int $basePrice): void
    {
        for ($day = 0; $day <= 6; $day++) {
            // Morning: lower price
            CourtSchedule::create([
                'court_id' => $court->id,
                'day_of_week' => $day,
                'start_time' => '06:00',
                'end_time' => '11:00',
                'price' => $basePrice,
                'effective_from' => '2026-01-01',
            ]);
            // Afternoon
            CourtSchedule::create([
                'court_id' => $court->id,
                'day_of_week' => $day,
                'start_time' => '11:00',
                'end_time' => '17:00',
                'price' => (int) ($basePrice * 1.2),
                'effective_from' => '2026-01-01',
            ]);
            // Evening: peak price
            CourtSchedule::create([
                'court_id' => $court->id,
                'day_of_week' => $day,
                'start_time' => '17:00',
                'end_time' => '22:00',
                'price' => (int) ($basePrice * 1.5),
                'effective_from' => '2026-01-01',
            ]);
        }
    }
}
