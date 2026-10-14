<?php

namespace Database\Seeders;

use App\Models\Pitch;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (Pitch::query()->exists()) {
            return;
        }

        Pitch::query()->insert([
            [
                'name' => 'Sân A1 - Sân 5 người cỏ nhân tạo',
                'type' => 5,
                'price_per_hour' => 250000,
                'address' => 'Ngõ 02, ngách 60, Cầu Giấy, Hà Nội',
                'opening_time' => '06:00:00',
                'closing_time' => '23:00:00',
                'hotline' => '0901 234 567',
                'pitch_size' => 'Sân 5 người',
                'field_count' => 1,
                'is_open' => true,
                'description' => 'Sân 5 người cỏ nhân tạo mới, lưới bao quanh đầy đủ, hệ thống chiếu sáng LED.',
                'status' => 'active',
                'created_at' => now(),
            ],
            [
                'name' => 'Sân A2 - Sân 5 người cỏ tự nhiên',
                'type' => 5,
                'price_per_hour' => 250000,
                'address' => 'Ngõ 02, ngách 60, Cầu Giấy, Hà Nội',
                'opening_time' => '06:00:00',
                'closing_time' => '23:00:00',
                'hotline' => '0902 345 678',
                'pitch_size' => 'Sân 5 người',
                'field_count' => 1,
                'is_open' => true,
                'description' => 'Sân 5 người cỏ tự nhiên, môi trường trong lành, phù hợp luyện tập nhẹ.',
                'status' => 'active',
                'created_at' => now(),
            ],
            [
                'name' => 'Sân B1 - Sân 7 người cao cấp',
                'type' => 7,
                'price_per_hour' => 250000,
                'address' => 'Ngõ 02, ngách 60, Cầu Giấy, Hà Nội',
                'opening_time' => '06:00:00',
                'closing_time' => '23:00:00',
                'hotline' => '0903 456 789',
                'pitch_size' => 'Sân 7 người',
                'field_count' => 1,
                'is_open' => true,
                'description' => 'Sân 7 người tiêu chuẩn, cỏ nhân tạo dày, bóng đèn, phòng thay đồ riêng.',
                'status' => 'active',
                'created_at' => now(),
            ],
            [
                'name' => 'Sân C1 - Sân 11 người tiêu chuẩn',
                'type' => 11,
                'price_per_hour' => 250000,
                'address' => 'Ngõ 02, ngách 60, Cầu Giấy, Hà Nội',
                'opening_time' => '06:00:00',
                'closing_time' => '23:00:00',
                'hotline' => '0904 567 890',
                'pitch_size' => 'Sân 11 người',
                'field_count' => 1,
                'is_open' => true,
                'description' => 'Sân 11 người kích thước chuẩn FIFA, cỏ tự nhiên, khán đài, hệ thống chiếu sáng chuyên nghiệp.',
                'status' => 'active',
                'created_at' => now(),
            ],
        ]);
    }
}
