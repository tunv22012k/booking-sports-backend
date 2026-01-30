<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VenueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Realistic Manual Data (Da Nang) - Kept for specific testing
        $manualVenues = [
            [
                'name' => 'Sân Cầu Lông Quân Khu 5',
                'type' => 'badminton',
                'location' => ['lat' => 16.0372, 'lng' => 108.2120],
                'address' => '7 Duy Tân, Hòa Cường Bắc, Hải Châu, Đà Nẵng',
                'price' => 80000,
                'pricing_type' => 'hour',
                'description' => 'Sân tiêu chuẩn thi đấu, thoáng mát, trung tâm thành phố.',
            ],
            [
                'name' => 'Sân Bóng Đá Tuyên Sơn',
                'type' => 'football',
                'location' => ['lat' => 16.0336, 'lng' => 108.2238],
                'address' => 'Làng thể thao Tuyên Sơn, Hải Châu, Đà Nẵng',
                'price' => 300000,
                'pricing_type' => 'match',
                'description' => 'Cụm sân cỏ nhân tạo lớn nhất Đà Nẵng, dịch vụ tốt.',
            ],
            [
                'name' => 'Sân Tennis Công Viên 29/3',
                'type' => 'tennis',
                'location' => ['lat' => 16.0610, 'lng' => 108.2045],
                'address' => 'Công viên 29/3, Thanh Khê, Đà Nẵng',
                'price' => 150000,
                'pricing_type' => 'hour',
                'description' => 'Không gian xanh mát, yên tĩnh, mặt sân cứng.',
            ],
        ];

        // Ensure we have a default user for reviews
        $user = \App\Models\User::first() ?? \App\Models\User::factory()->create([
            'name' => 'Reviewer',
            'email' => 'reviewer@example.com',
        ]);

        // Insert Manual Venues
        foreach ($manualVenues as $v) {
            // Check if exists to avoid dupes on re-seed
            if (\App\Models\Venue::where('name', $v['name'])->exists()) continue;

            $venue = \App\Models\Venue::create([
                'name' => $v['name'],
                'type' => $v['type'],
                'lat' => $v['location']['lat'],
                'lng' => $v['location']['lng'],
                'address' => $v['address'],
                'price' => $v['price'],
                'pricing_type' => $v['pricing_type'],
                'description' => $v['description'],
                'rating' => 5.0,
            ]);

            // Add default courts
            $venue->courts()->create(['name' => 'Sân 1', 'type' => $v['type']]);
            $venue->courts()->create(['name' => 'Sân 2', 'type' => $v['type']]);
        }

        // 2. Generated Data for Major Cities (Aiming for ~1000 total)
        $cities = [
            'Ha Noi' => ['lat' => 21.0285, 'lng' => 105.8542],
            'Ho Chi Minh' => ['lat' => 10.8231, 'lng' => 106.6297],
            'Da Nang' => ['lat' => 16.0544, 'lng' => 108.2022],
            'Hai Phong' => ['lat' => 20.8449, 'lng' => 106.6881],
            'Can Tho' => ['lat' => 10.0452, 'lng' => 105.7469],
            'Bien Hoa' => ['lat' => 10.9575, 'lng' => 106.8427],
            'Nha Trang' => ['lat' => 12.2388, 'lng' => 109.1967],
            'Hue' => ['lat' => 16.4637, 'lng' => 107.5909],
            'Buon Ma Thuot' => ['lat' => 12.6675, 'lng' => 108.0383],
            'Vinh' => ['lat' => 18.6733, 'lng' => 105.6871],
            'Vung Tau' => ['lat' => 10.3460, 'lng' => 107.0843],
            'Quy Nhon' => ['lat' => 13.7830, 'lng' => 109.2197],
            'Long Xuyen' => ['lat' => 10.3759, 'lng' => 105.4185],
            'Thai Nguyen' => ['lat' => 21.5942, 'lng' => 105.8482],
            'Thanh Hoa' => ['lat' => 19.8077, 'lng' => 105.7766],
            'Nam Dinh' => ['lat' => 20.4190, 'lng' => 106.1738],
            'Viet Tri' => ['lat' => 21.3216, 'lng' => 105.3995],
            'Phan Thiet' => ['lat' => 10.9254, 'lng' => 108.1032],
            'Ca Mau' => ['lat' => 9.1764, 'lng' => 105.1524],
            'Da Lat' => ['lat' => 11.9404, 'lng' => 108.4583],
            'Pleiku' => ['lat' => 13.9740, 'lng' => 108.0039],
            'My Tho' => ['lat' => 10.3541, 'lng' => 106.3664],
            'Rach Gia' => ['lat' => 10.0076, 'lng' => 105.0772],
            'Dong Hoi' => ['lat' => 17.4765, 'lng' => 106.5984],
            'Ha Long' => ['lat' => 20.9499, 'lng' => 107.0733],
        ];

        $types = ['football', 'badminton', 'tennis', 'pickleball', 'basketball', 'swimming', 'gym'];

        foreach ($cities as $cityName => $coords) {
            // Generate ~40 venues per city * 25 cities = 1000 venues
            for ($i = 1; $i <= 40; $i++) {
                $type = $types[array_rand($types)];
                // Random offset within ~10km (0.08 degrees approx)
                $latOffset = (mt_rand(-800, 800) / 10000); 
                $lngOffset = (mt_rand(-800, 800) / 10000);
                
                $lat = $coords['lat'] + $latOffset;
                $lng = $coords['lng'] + $lngOffset;

                $name = "Sân $type $cityName #$i";
                
                \App\Models\Venue::create([
                    'name' => $name,
                    'type' => $type,
                    'lat' => $lat,
                    'lng' => $lng,
                    'address' => "Địa chỉ ngẫu nhiên tại $cityName",
                    'price' => (mt_rand(5, 50) * 10000),
                    'pricing_type' => 'hour',
                    'description' => "Sân tập $type chất lượng cao tại $cityName.",
                    'rating' => mt_rand(35, 50) / 10,
                ]);
            }
        }
    }
}
