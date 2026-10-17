<?php

namespace App\Controllers\Admin;

use App\Controller;
use App\Models\Pitch;
use App\Models\User;
use Rakit\Validation\Validator;

class PitchController extends Controller
{
    protected $pitch;
    protected $validator;
    protected $user;

    public function __construct()
    {
        $this->pitch     = new Pitch();
        $this->validator = new Validator();
        $this->user      = new User();

        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!$this->user->canAccessAdmin()) {
            if (!$this->user->getActiveUser()) {
                setFlash('error', 'Vui lòng đăng nhập bằng tài khoản ADMIN để truy cập trang quản lý');
                redirect('login');
            }
            setFlash('error', 'Tài khoản USER không có quyền truy cập trang quản trị.');
            redirect('/');
        }
    }

    public function index()
    {
        $pitches = array_values(array_filter(
            $this->pitch->all(),
            static fn ($pitch) => in_array((int)$pitch['type'], [5, 7], true)
        ));

        $success = getFlash('success');
        $error   = getFlash('error');

        return view('admin.pitches.index', [
            'pitches' => $pitches,
            'success' => $success,
            'error'   => $error,
            'user'    => $this->user->getActiveUser(),
            'canDelete' => $this->user->canDelete(),
        ]);
    }

    public function show($id)
    {
        $pitch = $this->pitch->find($id);
        if (!$pitch) {
            redirect404();
            return;
        }

        return view('admin.pitches.show', [
            'pitch' => $pitch,
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

    private function validatePitchHours(array $data)
    {
        $openingTime = $data['opening_time'] ?? '';
        $closingTime = $data['closing_time'] ?? '';
        $errors = [];

        if (!is_string($openingTime) || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $openingTime)) {
            $errors['opening_time'] = 'Giờ mở cửa không hợp lệ';
        }
        if (!is_string($closingTime) || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $closingTime)) {
            $errors['closing_time'] = 'Giờ đóng cửa không hợp lệ';
        }

        if (!$errors) {
            if ($openingTime >= $closingTime) {
                $errors['opening_time'] = 'Giờ mở cửa phải trước giờ đóng cửa';
            } elseif ($openingTime < '06:00' || $closingTime > '23:00') {
                $errors['opening_time'] = 'Khung giờ hoạt động phải nằm trong khoảng 06:00 - 23:00';
            }
        }

        return $errors;
    }

    public function store()
    {
        $data = $_POST;
        keepOld($data);

        $rules = [
            'name'           => 'required|min:3|max:100',
            'type'           => 'required|in:5,7',
            'price_per_hour' => 'required|numeric|min:0',
            'description'    => 'nullable|max:2000',
            'address'        => 'nullable|max:255',
            'opening_time'   => 'required',
            'closing_time'   => 'required',
            'grass_quality'  => 'nullable|max:100',
            'has_lighting'   => 'required|in:0,1',
            'has_changing_room' => 'required|in:0,1',
            'has_parking'    => 'required|in:0,1',
            'extra_services' => 'nullable|max:1000',
            'status'         => 'required|in:active,inactive',
        ];

        $errors = $this->validate($this->validator, $data, $rules);
        if (!$errors) {
            $errors = $this->validatePitchHours($data);
        }

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
            'description'    => trim($data['description'] ?? ''),
            'image'          => $imagePath,
            'address'        => trim($data['address'] ?? ''),
            'opening_time'   => $data['opening_time'] . ':00',
            'closing_time'   => $data['closing_time'] . ':00',
            'grass_quality'  => trim($data['grass_quality'] ?? ''),
            'has_lighting'   => (int)$data['has_lighting'],
            'has_changing_room' => (int)$data['has_changing_room'],
            'has_parking'    => (int)$data['has_parking'],
            'extra_services' => trim($data['extra_services'] ?? ''),
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
        if (!in_array((int)$pitch['type'], [5, 7], true)) {
            setFlash('error', 'Sân loại này đã ngừng hoạt động và chỉ được lưu để giữ lịch sử đặt sân.');
            redirect('admin/pitches');
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
        if (!in_array((int)$pitch['type'], [5, 7], true)) {
            setFlash('error', 'Không thể sửa sân loại đã ngừng hoạt động.');
            redirect('admin/pitches');
            return;
        }

        $data = $_POST;
        keepOld($data);

        $rules = [
            'name'           => 'required|min:3|max:100',
            'type'           => 'required|in:5,7',
            'price_per_hour' => 'required|numeric|min:0',
            'description'    => 'nullable|max:2000',
            'address'        => 'nullable|max:255',
            'opening_time'   => 'required',
            'closing_time'   => 'required',
            'grass_quality'  => 'nullable|max:100',
            'has_lighting'   => 'required|in:0,1',
            'has_changing_room' => 'required|in:0,1',
            'has_parking'    => 'required|in:0,1',
            'extra_services' => 'nullable|max:1000',
            'status'         => 'required|in:active,inactive',
        ];

        $errors = $this->validate($this->validator, $data, $rules);
        if (!$errors) {
            $errors = $this->validatePitchHours($data);
        }

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
            'description'    => trim($data['description'] ?? ''),
            'address'        => trim($data['address'] ?? ''),
            'opening_time'   => $data['opening_time'] . ':00',
            'closing_time'   => $data['closing_time'] . ':00',
            'grass_quality'  => trim($data['grass_quality'] ?? ''),
            'has_lighting'   => (int)$data['has_lighting'],
            'has_changing_room' => (int)$data['has_changing_room'],
            'has_parking'    => (int)$data['has_parking'],
            'extra_services' => trim($data['extra_services'] ?? ''),
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
        if (!in_array((int)$pitch['type'], [5, 7], true)) {
            setFlash('error', 'Không thể kích hoạt lại sân loại đã ngừng kinh doanh.');
            redirect('admin/pitches');
            return;
        }

        $newStatus = $pitch['status'] === 'active' ? 'inactive' : 'active';

        $this->pitch->update($id, [
            'name'           => $pitch['name'],
            'type'           => (int)$pitch['type'],
            'price_per_hour' => (int)$pitch['price_per_hour'],
            'description'    => $pitch['description'] ?? '',
            'status'         => $newStatus,
        ]);

        setFlash('success', $newStatus === 'active' ? 'Kích hoạt sân thành công' : 'Tạm dừng sân thành công');
        redirect('admin/pitches');
    }

    public function destroy($id)
    {
        if (!$this->user->canDelete()) {
            setFlash('error', 'Chỉ ADMIN mới có quyền xóa sân.');
            redirect('admin/pitches');
            return;
        }

        $pitch = $this->pitch->find($id);
        if (!$pitch) {
            redirect404();
            return;
        }
        if (!in_array((int)$pitch['type'], [5, 7], true)) {
            setFlash('error', 'Sân đã có loại ngừng kinh doanh được giữ lại để bảo toàn lịch sử đặt sân.');
            redirect('admin/pitches');
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
