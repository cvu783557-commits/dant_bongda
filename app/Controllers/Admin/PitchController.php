<?php

namespace App\Controllers\Admin;

use App\Controller;
use App\Models\Booking;
use App\Models\Pitch;
use Rakit\Validation\Validator;

class PitchController extends Controller
{
    protected $pitch;
    protected $validator;

    public function __construct()
    {
        $this->pitch     = new Pitch();
        $this->validator = new Validator();

        if (empty($_SESSION['auth_user_id'])) {
            setFlash('error', 'Vui lòng đăng nhập để truy cập trang quản trị.');
            redirect('admin/login');
        }
    }

    public function index()
    {
        $pitches = $this->pitch->all();

        $success = getFlash('success');
        $error   = getFlash('error');

        return view('admin.pitches.index', [
            'pitches' => $pitches,
            'success' => $success,
            'error'   => $error,
        ]);
    }

    public function show($id)
    {
        $pitch = $this->pitch->find($id);
        if (!$pitch) {
            redirect404();
            return;
        }

        $bookings = (new Booking())->all(['pitch_id' => $id]);
        $now = new \DateTimeImmutable();
        $upcomingBookings = array_values(array_filter($bookings, function ($booking) use ($now) {
            if (!in_array($booking['status'], ['pending', 'confirmed'], true)) {
                return false;
            }

            return new \DateTimeImmutable($booking['booking_date'] . ' ' . $booking['start_time']) > $now;
        }));
        usort($upcomingBookings, function ($first, $second) {
            return [$first['booking_date'], $first['start_time']] <=> [$second['booking_date'], $second['start_time']];
        });

        return view('admin.pitches.show', [
            'pitch' => $pitch,
            'bookingCount' => count($bookings),
            'upcomingBookings' => $upcomingBookings,
        ]);
    }

    public function create()
    {
        $old   = $_SESSION['old'] ?? [];
        $error = getFlash('error');

        return view('admin.pitches.create', [
            'old'   => $old,
            'error' => $error,
        ]);
    }

    public function store()
    {
        $data = $_POST;
        keepOld($data);

        $rules = [
            'name'           => 'required|min:3|max:100',
            'type'           => 'required|in:5,7,11',
            'price_per_hour' => 'required|numeric|min:0',
            'address'        => 'nullable|max:255',
            'opening_time'   => 'nullable|date_format:H:i',
            'closing_time'   => 'nullable|date_format:H:i',
            'hotline'        => 'nullable|max:30',
            'pitch_size'     => 'nullable|max:100',
            'field_count'    => 'required|integer|min:1',
            'is_open'        => 'required|in:0,1',
            'description'    => 'nullable|max:2000',
            'status'         => 'required|in:active,inactive',
        ];

        $errors = $this->validate($this->validator, $data, $rules);

        $imagePath = null;
        if (is_upload('image')) {
            try {
                $imagePath = $this->uploadFile($_FILES['image'], 'pitches');
            } catch (\Exception $e) {
                $errors['image'] = 'Lỗi upload ảnh: ' . $e->getMessage();
            }
        }

        if (!empty($errors)) {
            setFlash('error', reset($errors));
            redirect('admin/pitches/create');
            return;
        }

        $id = $this->pitch->create([
            'name'           => trim($data['name']),
            'type'           => (int)$data['type'],
            'price_per_hour' => (int)$data['price_per_hour'],
            'address'        => trim($data['address'] ?? ''),
            'opening_time'   => $data['opening_time'] ?? '06:00',
            'closing_time'   => $data['closing_time'] ?? '23:00',
            'hotline'        => trim($data['hotline'] ?? ''),
            'pitch_size'     => trim($data['pitch_size'] ?? ''),
            'field_count'    => (int)($data['field_count'] ?? 1),
            'is_open'        => isset($data['is_open']) ? (int)$data['is_open'] : 1,
            'description'    => trim($data['description'] ?? ''),
            'image'          => $imagePath,
            'status'         => $data['status'],
        ]);

        unset($_SESSION['old']);
        setFlash('success', 'Thêm sân thành công');
        redirect('admin/pitches');
    }

    public function edit($id)
    {
        $pitch = $this->pitch->find($id);
        if (!$pitch) {
            redirect404();
            return;
        }

        $old   = $_SESSION['old'] ?? [];
        $error = getFlash('error');

        return view('admin.pitches.edit', [
            'pitch' => $pitch,
            'old'   => $old,
            'error' => $error,
        ]);
    }

    public function update($id)
    {
        $pitch = $this->pitch->find($id);
        if (!$pitch) {
            redirect404();
            return;
        }

        $data = $_POST;
        keepOld($data);

        $rules = [
            'name'           => 'required|min:3|max:100',
            'type'           => 'required|in:5,7,11',
            'price_per_hour' => 'required|numeric|min:0',
            'address'        => 'nullable|max:255',
            'opening_time'   => 'nullable|date_format:H:i',
            'closing_time'   => 'nullable|date_format:H:i',
            'hotline'        => 'nullable|max:30',
            'pitch_size'     => 'nullable|max:100',
            'field_count'    => 'required|integer|min:1',
            'is_open'        => 'required|in:0,1',
            'description'    => 'nullable|max:2000',
            'status'         => 'required|in:active,inactive',
        ];

        $errors = $this->validate($this->validator, $data, $rules);

        $imagePath = null;
        $changeImage = false;
        if (is_upload('image')) {
            try {
                $imagePath = $this->uploadFile($_FILES['image'], 'pitches');
                $changeImage = true;
            } catch (\Exception $e) {
                $errors['image'] = 'Lỗi upload ảnh: ' . $e->getMessage();
            }
        }

        if (!empty($errors)) {
            setFlash('error', reset($errors));
            redirect('admin/pitches/' . $id . '/edit');
            return;
        }

        $updateData = [
            'name'           => trim($data['name']),
            'type'           => (int)$data['type'],
            'price_per_hour' => (int)$data['price_per_hour'],
            'address'        => trim($data['address'] ?? ''),
            'opening_time'   => $data['opening_time'] ?? '06:00',
            'closing_time'   => $data['closing_time'] ?? '23:00',
            'hotline'        => trim($data['hotline'] ?? ''),
            'pitch_size'     => trim($data['pitch_size'] ?? ''),
            'field_count'    => (int)($data['field_count'] ?? 1),
            'is_open'        => isset($data['is_open']) ? (int)$data['is_open'] : 1,
            'description'    => trim($data['description'] ?? ''),
            'status'         => $data['status'],
        ];
        if ($changeImage) {
            $updateData['image'] = $imagePath;
        }

        $this->pitch->update($id, $updateData);

        unset($_SESSION['old']);
        setFlash('success', 'Cập nhật sân thành công');
        redirect('admin/pitches');
    }

    public function toggleStatus($id)
    {
        $pitch = $this->pitch->find($id);
        if (!$pitch) {
            redirect404();
            return;
        }

        $newStatus = $pitch['status'] === 'active' ? 'inactive' : 'active';

        $this->pitch->update($id, [
            'name'           => $pitch['name'],
            'type'           => (int)$pitch['type'],
            'price_per_hour' => (int)$pitch['price_per_hour'],
            'address'        => $pitch['address'] ?? '',
            'opening_time'   => $pitch['opening_time'] ?? '06:00:00',
            'closing_time'   => $pitch['closing_time'] ?? '23:00:00',
            'hotline'        => $pitch['hotline'] ?? '',
            'pitch_size'     => $pitch['pitch_size'] ?? '',
            'field_count'    => (int)($pitch['field_count'] ?? 1),
            'is_open'        => (int)($pitch['is_open'] ?? 1),
            'description'    => $pitch['description'] ?? '',
            'status'         => $newStatus,
        ]);

        setFlash('success', $newStatus === 'active' ? 'Kích hoạt sân thành công' : 'Tạm dừng sân thành công');
        redirect('admin/pitches');
    }

    public function destroy($id)
    {
        $pitch = $this->pitch->find($id);
        if (!$pitch) {
            redirect404();
            return;
        }

        try {
            $this->pitch->delete($id);
            setFlash('success', 'Xóa sân thành công');
        } catch (\Exception $e) {
            setFlash('error', 'Không thể xóa sân này (đã có đơn đặt)');
        }

        redirect('admin/pitches');
    }
}
