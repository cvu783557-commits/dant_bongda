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
        if (!$this->user->getActiveUser()) {
            setFlash('error', 'Vui lòng đăng nhập để truy cập quản lý');
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
            'user'    => $this->user->getActiveUser(),
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

    public function store()
    {
        $data = $_POST;
        keepOld($data);

        $rules = [
            'name'           => 'required|min:3|max:100',
            'type'           => 'required|in:5,7,11',
            'price_per_hour' => 'required|numeric|min:0',
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
