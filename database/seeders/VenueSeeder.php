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
        $venues = [
            [
                'name' => 'Sân Cầu Lông Quân Khu 5',
                'type' => 'badminton',
                'location' => ['lat' => 16.0372, 'lng' => 108.2120],
                'address' => '7 Duy Tân, Hòa Cường Bắc, Hải Châu, Đà Nẵng',
                'price_info' => '80,000 VND/h',
                'description' => 'Sân tiêu chuẩn thi đấu, thoáng mát, trung tâm thành phố.',
                'courts' => [
                    ['name' => 'Sân 1'],
                    ['name' => 'Sân 2'],
                    // Generate Sân 3 - Sân 10
                ],
                'extras' => [
                    ['name' => 'Thuê vợt', 'price' => 20000],
                    ['name' => 'Nước suối', 'price' => 10000],
                    ['name' => 'Trọng tài', 'price' => 50000],
                ],
                'reviews' => [
                    ['userName' => 'Nguyen Van A', 'rating' => 5, 'comment' => 'Sân đẹp, thoáng mát.', 'date' => '2023-10-20'],
                    ['userName' => 'Tran Thi B', 'rating' => 4, 'comment' => 'Giá cả hợp lý.', 'date' => '2023-10-21'],
                ]
            ],
            [
                'name' => 'Sân Bóng Đá Tuyên Sơn',
                'type' => 'football',
                'location' => ['lat' => 16.0336, 'lng' => 108.2238],
                'address' => 'Làng thể thao Tuyên Sơn, Hải Châu, Đà Nẵng',
                'price_info' => '300,000 VND/trận',
                'description' => 'Cụm sân cỏ nhân tạo lớn nhất Đà Nẵng, dịch vụ tốt.',
                'courts' => [
                    ['name' => 'Sân A']
                ],
                'extras' => [
                    ['name' => 'Áo bib', 'price' => 10000],
                    ['name' => 'Trọng tài', 'price' => 100000],
                    ['name' => 'Nước bình 20L', 'price' => 20000],
                ],
                'reviews' => [
                    ['userName' => 'Le Van C', 'rating' => 5, 'comment' => 'Sân cỏ chất lượng tốt.', 'date' => '2023-10-22'],
                ]
            ],
            [
                'name' => 'Sân Tennis Công Viên 29/3',
                'type' => 'tennis',
                'location' => ['lat' => 16.0610, 'lng' => 108.2045],
                'address' => 'Công viên 29/3, Thanh Khê, Đà Nẵng',
                'price_info' => '150,000 VND/h',
                'description' => 'Không gian xanh mát, yên tĩnh, mặt sân cứng.',
                'courts' => [
                    ['name' => 'Sân Chính']
                ],
                'extras' => [
                    ['name' => 'Nhặt bóng', 'price' => 50000],
                ],
                'reviews' => []
            ],
            [
                'name' => 'Sân Cầu Lông Chi Lăng',
                'type' => 'badminton',
                'location' => ['lat' => 16.0718, 'lng' => 108.2215],
                'address' => 'SVĐ Chi Lăng, Hải Châu, Đà Nẵng',
                'price_info' => '70,000 VND/h',
                'description' => 'Sân lâu đời, giá bình dân, cộng đồng chơi đông.',
                'courts' => [], // Will need realistic courts for booking
                'extras' => [],
                'reviews' => []
            ],
            [
                'name' => 'Sân Bóng Chuyên Việt',
                'type' => 'football',
                'location' => ['lat' => 16.0594, 'lng' => 108.2435],
                'address' => 'An Đồn, Sơn Trà, Đà Nẵng',
                'price_info' => '350,000 VND/trận',
                'description' => 'Sân mới, cỏ đẹp, gần biển.',
                'courts' => [],
                'extras' => [],
                'reviews' => []
            ]
        ];

        // Ensure we have a default user for reviews
        $user = \App\Models\User::first() ?? \App\Models\User::factory()->create([
            'name' => 'Reviewer',
            'email' => 'reviewer@example.com',
        ]);

        foreach ($venues as $v) {
            $venue = \App\Models\Venue::create([
                'name' => $v['name'],
                'type' => $v['type'],
                'lat' => $v['location']['lat'],
                'lng' => $v['location']['lng'],
                'address' => $v['address'],
                'price_info' => $v['price_info'],
                'description' => $v['description'],
                'rating' => 5.0, // Default or calc
            ]);

            // Courts
            if (empty($v['courts'])) {
                // If empty, generate default court "Sân 1"
                $venue->courts()->create(['name' => 'Sân 1', 'type' => $v['type']]);
                if ($v['name'] == 'Sân Cầu Lông Chi Lăng') {
                     $venue->courts()->create(['name' => 'Sân 3', 'type' => $v['type']]); // For mock booking match
                }
            } else {
                foreach ($v['courts'] as $c) {
                    $venue->courts()->create(['name' => $c['name'], 'type' => $v['type']]);
                }
                // Generate range courts for QK5
                if ($v['name'] == 'Sân Cầu Lông Quân Khu 5') {
                    for ($i = 3; $i <= 10; $i++) {
                        $venue->courts()->create(['name' => "Sân $i", 'type' => 'badminton']);
                    }
                }
            }

            // Extras
            foreach ($v['extras'] as $e) {
                $venue->extras()->create($e);
            }

            // Reviews
            foreach ($v['reviews'] as $r) {
                // We fake the user for review
                $venue->reviews()->create([
                    'user_id' => $user->id,
                    'rating' => $r['rating'],
                    'comment' => $r['comment'],
                    'date' => $r['date']
                ]);
            }
        }
    }
}
