<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    private function createPitch(): int
    {
        return DB::table('pitches')->insertGetId([
            'name' => 'Sân kiểm thử',
            'type' => 5,
            'price_per_hour' => 250000,
            'address' => 'Ngõ 02, ngách 60, Cầu Giấy, Hà Nội',
            'opening_time' => '06:00:00',
            'closing_time' => '23:00:00',
            'field_count' => 2,
            'is_open' => true,
            'status' => 'active',
            'created_at' => now(),
        ]);
    }
}
