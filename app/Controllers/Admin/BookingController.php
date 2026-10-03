<?php

namespace App\Controllers\Admin;

use App\Controller;
use App\Models\Booking;
use App\Models\Pitch;
use Rakit\Validation\Validator;

class BookingController extends Controller
{
    protected $booking;
    protected $pitch;
    protected $validator;

    public function __construct()
    {
        $this->booking   = new Booking();
        $this->pitch     = new Pitch();
        $this->validator = new Validator();
    }

    public function index()
    {
        $filters = [
            'status'       => $_GET['status'] ?? '',
            'booking_date' => $_GET['booking_date'] ?? '',
            'pitch_id'     => $_GET['pitch_id'] ?? '',
        ];

        $bookings = $this->booking->all($filters);
        $pitches  = $this->pitch->all();

        $success = getFlash('success');
        $error   = getFlash('error');

        return view('admin.bookings.index', [
            'bookings' => $bookings,
            'pitches'  => $pitches,
            'filters'  => $filters,
            'success'  => $success,
            'error'    => $error,
        ]);
    }

    public function edit($id)
    {
        $booking = $this->booking->find($id);
        if (!$booking) {
            redirect404();
            return;
        }

        $old   = $_SESSION['old'] ?? [];
        $error = getFlash('error');

        return view('admin.bookings.edit', [
            'booking' => $booking,
            'old'     => $old,
            'error'   => $error,
        ]);
    }

    public function update($id)
    {
        $booking = $this->booking->find($id);
        if (!$booking) {
            redirect404();
            return;
        }

        $data = $_POST;
        keepOld($data);

        $rules = [
            'status' => 'required|in:pending,confirmed,cancelled',
            'notes'  => 'nullable|max:500',
        ];

        $errors = $this->validate($this->validator, $data, $rules);

        if (!empty($errors)) {
            setFlash('error', reset($errors));
            redirect('admin/bookings/' . $id . '/edit');
            return;
        }

        $this->booking->update($id, [
            'status' => $data['status'],
            'notes'  => trim($data['notes'] ?? ''),
        ]);

        unset($_SESSION['old']);
        setFlash('success', 'Cập nhật trạng thái đặt sân thành công');
        redirect('admin/bookings');
    }

    public function destroy($id)
    {
        $booking = $this->booking->find($id);
        if (!$booking) {
            redirect404();
            return;
        }

        $this->booking->delete($id);
        setFlash('success', 'Xóa đơn đặt sân thành công');
        redirect('admin/bookings');
    }

    public function dashboard()
    {
        $totalToday    = $this->booking->countToday();
        $totalRevenue  = $this->booking->revenueConfirmed();
        $totalActive   = $this->pitch->count();

        $recentBookings = $this->booking->all(['booking_date' => date('Y-m-d')]);
        if (count($recentBookings) === 0) {
            $recentBookings = $this->booking->all();
            $recentBookings = array_slice($recentBookings, 0, 10);
        }

        $pitches = $this->pitch->all();

        $success = getFlash('success');
        $error   = getFlash('error');

        return view('admin.dashboard', [
            'totalToday'     => $totalToday,
            'totalRevenue'   => $totalRevenue,
            'totalActive'    => $totalActive,
            'recentBookings' => $recentBookings,
            'pitches'        => $pitches,
            'success'        => $success,
            'error'          => $error,
        ]);
    }
}
