<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pitch;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PitchController extends Controller
{
    public function index(): View
    {
        return view('admin.pitches.index', [
            'pitches' => Pitch::query()->orderBy('id')->get(),
            'success' => session('success'),
            'error' => session('error'),
        ]);
    }

    public function show(int $id): View
    {
        $pitch = Pitch::query()->findOrFail($id);
        $bookingsQuery = DB::table('bookings')
            ->join('time_slots as ts', 'ts.id', '=', 'bookings.time_slot_id')
            ->where('bookings.pitch_id', $pitch->id)
            ->select([
                'bookings.id',
                'bookings.customer_name',
                'bookings.customer_phone',
                'bookings.booking_date',
                'bookings.child_pitch_no',
                'bookings.duration_hours',
                'bookings.total_price',
                'bookings.status',
                'bookings.created_at',
                'ts.start_time',
            ]);

        $upcomingBookings = (clone $bookingsQuery)
            ->whereIn('bookings.status', ['pending', 'confirmed'])
            ->whereDate('bookings.booking_date', '>=', now()->toDateString())
            ->orderBy('bookings.booking_date')
            ->orderBy('ts.start_time')
            ->get()
            ->filter(fn ($booking) => Carbon::parse($booking->booking_date . ' ' . $booking->start_time)->isFuture())
            ->values()
            ->map(fn ($booking) => $this->addBookingEndTime($booking))
            ->all();

        return view('admin.pitches.show', [
            'pitch' => $pitch,
            'upcomingBookings' => $upcomingBookings,
        ]);
    }

    private function addBookingEndTime(object $booking): array
    {
        $booking->end_time = Carbon::parse($booking->start_time)
            ->addHours((int) $booking->duration_hours)
            ->format('H:i:s');

        return (array) $booking;
    }

    public function create(): View
    {
        return view('admin.pitches.create', [
            'old' => session()->get('_old_input', []),
            'error' => session('error') ?? session('errors')?->first(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $data['name'] = trim($data['name']);
        $data['description'] = trim($data['description'] ?? '');
        $data['image'] = $request->hasFile('image')
            ? 'storage/' . $request->file('image')->store('pitches', 'public')
            : null;

        Pitch::query()->create($data);

        return redirect('/admin/pitches')->with('success', 'Thêm sân thành công.');
    }

    public function edit(int $id): View
    {
        return view('admin.pitches.edit', [
            'pitch' => Pitch::query()->findOrFail($id),
            'old' => session()->get('_old_input', []),
            'error' => session('error') ?? session('errors')?->first(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $pitch = Pitch::query()->findOrFail($id);
        $data = $request->validate($this->rules());
        $data['name'] = trim($data['name']);
        $data['description'] = trim($data['description'] ?? '');

        if ($request->hasFile('image')) {
            $data['image'] = 'storage/' . $request->file('image')->store('pitches', 'public');
        } else {
            unset($data['image']);
        }

        $pitch->update($data);

        return redirect('/admin/pitches')->with('success', 'Cập nhật sân thành công.');
    }

    public function toggleStatus(int $id): RedirectResponse
    {
        $pitch = Pitch::query()->findOrFail($id);
        $pitch->update(['status' => $pitch->status === 'active' ? 'inactive' : 'active']);

        return redirect('/admin/pitches')->with('success', 'Đã cập nhật trạng thái sân.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $pitch = Pitch::query()->findOrFail($id);
        if ($pitch->bookings()->exists()) {
            return redirect('/admin/pitches')->with('error', 'Không thể xóa sân đã có đơn đặt.');
        }

        $pitch->delete();

        return redirect('/admin/pitches')->with('success', 'Xóa sân thành công.');
    }

    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'type' => ['required', 'integer', 'in:5,7,11'],
            'price_per_hour' => ['required', 'numeric', 'min:0'],
            'address' => ['nullable', 'string', 'max:255'],
            'opening_time' => ['required', 'date_format:H:i'],
            'closing_time' => ['required', 'date_format:H:i', 'after:opening_time'],
            'hotline' => ['nullable', 'string', 'max:30'],
            'pitch_size' => ['nullable', 'string', 'max:100'],
            'field_count' => ['required', 'integer', 'min:1'],
            'is_open' => ['required', 'boolean'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'in:active,inactive'],
            'image' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
