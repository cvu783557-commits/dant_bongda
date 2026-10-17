<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Pitch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'status' => $request->query('status', ''),
            'booking_date' => $request->query('booking_date', ''),
            'pitch_id' => $request->query('pitch_id', ''),
        ];

        $query = $this->bookingQuery();
        if ($filters['status'] !== '') {
            $query->where('bookings.status', $filters['status']);
        }
        if ($filters['booking_date'] !== '') {
            $query->whereDate('bookings.booking_date', $filters['booking_date']);
        }
        if ($filters['pitch_id'] !== '') {
            $query->where('bookings.pitch_id', $filters['pitch_id']);
        }

        return view('admin.bookings.index', [
            'bookings' => $query->orderByDesc('bookings.booking_date')->orderByDesc('bookings.created_at')
                ->get()->map(fn ($booking) => $booking->toArray())->all(),
            'pitches' => Pitch::query()->orderBy('id')->get(),
            'filters' => $filters,
            'success' => session('success'),
            'error' => session('error'),
        ]);
    }

    public function edit(int $id): View
    {
        $booking = $this->bookingQuery()->where('bookings.id', $id)->firstOrFail();

        return view('admin.bookings.edit', [
            'booking' => $booking->toArray(),
            'old' => session()->get('_old_input', []),
            'error' => session('error') ?? session('errors')?->first(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,confirmed,cancelled'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        Booking::query()->findOrFail($id)->update([
            'status' => $data['status'],
            'notes' => trim($data['notes'] ?? ''),
        ]);

        return redirect('/admin/bookings')->with('success', 'Cập nhật trạng thái đặt sân thành công.');
    }

    public function destroy(int $id): RedirectResponse
    {
        Booking::query()->findOrFail($id)->delete();

        return redirect('/admin/bookings')->with('success', 'Xóa đơn đặt sân thành công.');
    }

    public function dashboard(): View
    {
        $recent = $this->bookingQuery()->whereDate('bookings.booking_date', now()->toDateString())
            ->orderByDesc('bookings.created_at')->get();
        if ($recent->isEmpty()) {
            $recent = $this->bookingQuery()->orderByDesc('bookings.booking_date')
                ->orderByDesc('bookings.created_at')->limit(10)->get();
        }

        return view('admin.dashboard', [
            'totalToday' => Booking::query()->whereDate('booking_date', now()->toDateString())->count(),
            'totalRevenue' => Booking::query()->where('status', 'confirmed')->sum('total_price'),
            'totalActive' => Pitch::query()->where('status', 'active')->count(),
            'recentBookings' => $recent->map(fn ($booking) => $booking->toArray())->all(),
            'pitches' => Pitch::query()->orderBy('id')->get(),
            'success' => session('success'),
            'error' => session('error'),
        ]);
    }

    private function bookingQuery()
    {
        return Booking::query()
            ->join('pitches as p', 'p.id', '=', 'bookings.pitch_id')
            ->join('time_slots as ts', 'ts.id', '=', 'bookings.time_slot_id')
            ->select([
                'bookings.*',
                'p.name as pitch_name',
                'p.type as pitch_type',
                'p.price_per_hour',
                'ts.start_time',
                DB::raw('DATE_ADD(ts.start_time, INTERVAL bookings.duration_hours HOUR) AS end_time'),
            ]);
    }
}
