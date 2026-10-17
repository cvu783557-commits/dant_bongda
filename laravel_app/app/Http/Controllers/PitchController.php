<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Pitch;
use App\Models\TimeSlot;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PitchController extends Controller
{
    public function index(): View
    {
        return view('home', [
            'pitches' => Pitch::query()->where('status', 'active')->orderBy('id')->get(),
            'success' => session('success'),
            'error' => session('error'),
        ]);
    }

    public function show(Request $request, int $id): View
    {
        $pitch = Pitch::query()->where('status', 'active')->findOrFail($id);
        $date = $request->query('date', now()->toDateString());
        if ($date < now()->toDateString()) {
            $date = now()->toDateString();
        }

        $reservations = Booking::query()
            ->join('time_slots as ts', 'ts.id', '=', 'bookings.time_slot_id')
            ->where('bookings.pitch_id', $pitch->id)
            ->whereDate('bookings.booking_date', $date)
            ->whereIn('bookings.status', ['pending', 'confirmed'])
            ->orderBy('bookings.child_pitch_no')
            ->orderBy('ts.start_time')
            ->get([
                'bookings.child_pitch_no',
                'ts.start_time',
                'bookings.duration_hours',
            ])
            ->map(fn ($reservation) => [
                'child_pitch_no' => $reservation->child_pitch_no,
                'start_time' => $reservation->start_time,
                'end_time' => Carbon::parse($reservation->start_time)
                    ->addHours((int) $reservation->duration_hours)
                    ->format('H:i:s'),
            ])
            ->all();

        return view('pitches.show', [
            'pitch' => $pitch,
            'slots' => TimeSlot::query()->orderBy('start_time')->get(),
            'date' => $date,
            'reservations' => $reservations,
            'success' => session('success'),
            'error' => session('error') ?? $request->session()->get('errors')?->first(),
            'old' => $request->session()->get('_old_input', []),
        ]);
    }

    public function bookingStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pitch_id' => ['required', 'integer', 'exists:pitches,id'],
            'customer_name' => ['required', 'string', 'min:2', 'max:100'],
            'customer_phone' => ['required', 'regex:/^0[0-9]{9,10}$/'],
            'customer_email' => ['nullable', 'email', 'max:100'],
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'time_slot_id' => ['required', 'integer', 'exists:time_slots,id'],
            'child_pitch_no' => ['required', 'integer', 'min:1'],
            'duration_hours' => ['required', 'integer', 'min:1', 'max:17'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $pitch = Pitch::query()->findOrFail($data['pitch_id']);
        if ($pitch->status !== 'active' || !$pitch->is_open) {
            return back()->withInput()->with('error', 'Sân hiện không nhận đặt chỗ.');
        }
        if ((int) $data['child_pitch_no'] > $pitch->field_count) {
            return back()->withInput()->with('error', 'Sân con không hợp lệ.');
        }

        $slot = TimeSlot::query()->findOrFail($data['time_slot_id']);
        $start = Carbon::parse($data['booking_date'] . ' ' . $slot->start_time);
        $end = $start->copy()->addHours((int) $data['duration_hours']);
        $opening = Carbon::parse($data['booking_date'] . ' ' . $pitch->opening_time);
        $closing = Carbon::parse($data['booking_date'] . ' ' . $pitch->closing_time);
        $slotStarts = TimeSlot::query()->pluck('start_time')->all();
        $slotEnds = TimeSlot::query()->pluck('end_time')->all();

        for ($hour = 0; $hour < (int) $data['duration_hours']; $hour++) {
            if (!in_array($start->copy()->addHours($hour)->format('H:i:s'), $slotStarts, true)) {
                return back()->withInput()->with('error', 'Thời lượng thuê vượt quá giờ hoạt động của sân.');
            }
        }

        if ($start->lt($opening) || $end->gt($closing) || !in_array($end->format('H:i:s'), $slotEnds, true)) {
            return back()->withInput()->with('error', 'Thời lượng thuê vượt quá giờ hoạt động của sân.');
        }
        if ($data['booking_date'] === now()->toDateString() && $start->lte(now())) {
            return back()->withInput()->with('error', 'Không thể đặt khung giờ đã qua.');
        }

        $overlap = Booking::query()
            ->join('time_slots as ts', 'ts.id', '=', 'bookings.time_slot_id')
            ->where('bookings.pitch_id', $pitch->id)
            ->where('bookings.child_pitch_no', $data['child_pitch_no'])
            ->whereDate('bookings.booking_date', $data['booking_date'])
            ->whereIn('bookings.status', ['pending', 'confirmed'])
            ->get(['ts.start_time', 'bookings.duration_hours'])
            ->contains(function ($reservation) use ($start, $end): bool {
                $reservedStart = Carbon::parse($reservation->start_time);
                $reservedEnd = $reservedStart->copy()->addHours((int) $reservation->duration_hours);

                return $reservedStart->lt($end) && $reservedEnd->gt($start);
            });

        if ($overlap) {
            return back()->withInput()->with('error', 'Khung giờ này không còn trống cho thời lượng đã chọn.');
        }

        $booking = Booking::query()->create([
            'pitch_id' => $pitch->id,
            'customer_name' => trim($data['customer_name']),
            'customer_phone' => trim($data['customer_phone']),
            'customer_email' => $data['customer_email'] ?? null,
            'booking_date' => $data['booking_date'],
            'time_slot_id' => $slot->id,
            'child_pitch_no' => (int) $data['child_pitch_no'],
            'duration_hours' => (int) $data['duration_hours'],
            'total_price' => (int) $pitch->price_per_hour * (int) $data['duration_hours'],
            'status' => 'pending',
            'notes' => trim($data['notes'] ?? ''),
        ]);

        return redirect('/bookings/success/' . $booking->id)
            ->with('success', 'Đặt sân thành công! Chúng tôi sẽ liên hệ xác nhận trong thời gian sớm nhất.');
    }

    public function bookingSuccess(int $id): View
    {
        $booking = Booking::query()
            ->join('pitches as p', 'p.id', '=', 'bookings.pitch_id')
            ->join('time_slots as ts', 'ts.id', '=', 'bookings.time_slot_id')
            ->where('bookings.id', $id)
            ->first([
                'bookings.*',
                'p.name as pitch_name',
                'p.type as pitch_type',
                'ts.start_time',
            ]);

        abort_if(!$booking, 404);
        $booking->end_time = Carbon::parse($booking->start_time)
            ->addHours((int) $booking->duration_hours)
            ->format('H:i:s');

        return view('bookings.success', [
            'booking' => $booking,
            'success' => session('success'),
            'error' => session('error'),
        ]);
    }
}
