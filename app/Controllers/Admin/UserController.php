<?php

namespace App\Controllers\Admin;

use App\Controller;
use App\Models\User;

class UserController extends Controller
{
    protected $user;

    public function __construct()
    {
        $this->user = new User();
        $currentUser = $this->user->getActiveUser();

        if (!$currentUser) {
            setFlash('error', 'Vui lòng đăng nhập để truy cập quản lý');
            redirect('admin/login');
        }
        if (($currentUser['role'] ?? '') !== 'ADMIN') {
            setFlash('error', 'Chỉ ADMIN mới được quản lý tài khoản và phân quyền.');
            redirect('admin/bookings');
        }
    }

    public function index()
    {
        return view('admin.users.index', [
            'users' => $this->user->all(),
            'currentUser' => $this->user->getActiveUser(),
            'success' => getFlash('success'),
            'error' => getFlash('error'),
            'csrfToken' => csrfToken(),
        ]);
    }

    public function updateAccess($id)
    {
        if (!isValidCsrfToken($_POST['_csrf_token'] ?? null)) {
            setFlash('error', 'Phiên làm việc không hợp lệ hoặc đã hết hạn. Vui lòng thử lại.');
            redirect('admin/users');
            return;
        }

        $currentUser = $this->user->getActiveUser();
        $targetId = (int)$id;
        $role = $_POST['role'] ?? '';
        $status = $_POST['status'] ?? '';

        if ($targetId === (int)$currentUser['id']) {
            setFlash('error', 'Không thể tự thay đổi quyền hoặc trạng thái tài khoản đang đăng nhập.');
            redirect('admin/users');
            return;
        }

        $result = $this->user->updateAccess($targetId, $role, $status);
        if (!$result['success']) {
            setFlash('error', $result['error']);
            redirect('admin/users');
            return;
        }

        setFlash('success', 'Đã cập nhật quyền và trạng thái tài khoản.');
        redirect('admin/users');
    }
}
