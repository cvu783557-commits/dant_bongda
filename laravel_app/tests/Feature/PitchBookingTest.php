<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Tests\TestCase;

class PitchBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_pitch_details_show_the_booking_information_and_current_policies(): void
    {
        $pitchId = $this->createPitch();

        $response = $this->get('/pitches/' . $pitchId);

        $response->assertOk()
            ->assertSee('Ngõ 02, ngách 60, Cầu Giấy, Hà Nội')
            ->assertSee('250.000 ₫')
            ->assertSee('Bảng giá thuê sân')
            ->assertSee('Chính sách đặt cọc')
            ->assertSee('Chính sách hủy sân / hoàn tiền')
            ->assertSee('Lưu ý an toàn')
            ->assertSee('Đặt sân ngay')
            ->assertDontSee('Nhận thông tin ưu đãi')
            ->assertDontSee('Google Maps');

        $this->post('/pitches/' . $pitchId . '/newsletter')->assertNotFound();
    }

    public function test_customer_can_book_a_child_pitch_for_multiple_hours(): void
    {
        $pitchId = $this->createPitch();
        $slotId = DB::table('time_slots')->where('start_time', '18:00:00')->value('id');

        $response = $this->post('/bookings', [
            'pitch_id' => $pitchId,
            'customer_name' => 'Nguyen Van A',
            'customer_phone' => '0901234567',
            'booking_date' => now()->addDay()->toDateString(),
            'time_slot_id' => $slotId,
            'child_pitch_no' => 2,
            'duration_hours' => 2,
        ]);

        $bookingId = DB::table('bookings')->value('id');
        $response->assertRedirect('/bookings/success/' . $bookingId);
        $this->assertDatabaseHas('bookings', [
            'id' => $bookingId,
            'pitch_id' => $pitchId,
            'child_pitch_no' => 2,
            'duration_hours' => 2,
            'total_price' => 500000,
            'status' => 'pending',
        ]);
        $this->get('/bookings/success/' . $bookingId)
            ->assertOk()
            ->assertSee('PB 2')
            ->assertSee('20:00');
    }

    public function test_admin_pitch_details_show_operating_information_and_related_bookings(): void
    {
        $pitchId = $this->createPitch();
        $slotId = DB::table('time_slots')->where('start_time', '18:00:00')->value('id');
        $upcomingDate = now()->addDays(2)->toDateString();

        $upcomingBookingId = DB::table('bookings')->insertGetId([
            'pitch_id' => $pitchId,
            'customer_name' => 'Khach Dat San',
            'customer_phone' => '0901234567',
            'booking_date' => $upcomingDate,
            'time_slot_id' => $slotId,
            'child_pitch_no' => 2,
            'duration_hours' => 2,
            'total_price' => 500000,
            'status' => 'pending',
            'created_at' => now(),
        ]);

        DB::table('bookings')->insert([
            'pitch_id' => $pitchId,
            'customer_name' => 'Khach Dat Cu',
            'customer_phone' => '0907654321',
            'booking_date' => now()->subDay()->toDateString(),
            'time_slot_id' => $slotId,
            'child_pitch_no' => 1,
            'duration_hours' => 1,
            'total_price' => 250000,
            'status' => 'cancelled',
            'created_at' => now()->subDay(),
        ]);

        $admin = User::query()->create([
            'username' => 'admin-test',
            'password_hash' => Hash::make('test-password'),
            'full_name' => 'Admin Test',
            'role' => 'ADMIN',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get('/admin/pitches/' . $pitchId);

        $response->assertOk()
            ->assertSee('Địa chỉ')
            ->assertSee('Giờ hoạt động')
            ->assertSee('0901 234 567')
            ->assertSee('Sân 5 người')
            ->assertSee('2 sân')
            ->assertSee('Đang mở nhận đặt')
            ->assertSee('Giá ngày thường')
            ->assertSee('Giá cuối tuần')
            ->assertDontSee('Tổng số đơn đặt liên quan')
            ->assertDontSee('Đơn sắp tới chưa hủy')
            ->assertDontSee('Giá thuê mỗi giờ')
            ->assertDontSee('Lịch đặt gần đây')
            ->assertSee('Khach Dat San')
            ->assertDontSee('Khach Dat Cu')
            ->assertSee('#' . str_pad($upcomingBookingId, 6, '0', STR_PAD_LEFT))
            ->assertSee('/admin/bookings?pitch_id=' . $pitchId);
    }

    private function createPitch(): int
    {
        return DB::table('pitches')->insertGetId([
            'name' => 'Sân kiểm thử',
            'type' => 5,
            'price_per_hour' => 250000,
            'address' => 'Ngõ 02, ngách 60, Cầu Giấy, Hà Nội',
            'opening_time' => '06:00:00',
            'closing_time' => '23:00:00',
            'hotline' => '0901 234 567',
            'pitch_size' => 'Sân 5 người',
            'field_count' => 2,
            'is_open' => true,
            'status' => 'active',
            'created_at' => now(),
        ]);
    }
}
