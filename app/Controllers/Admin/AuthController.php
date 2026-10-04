<?php

namespace App\Controllers\Admin;

use App\Controller;
use App\Models\User;

class AuthController extends Controller
{
    protected $user;

    public function __construct()
    {
        $this->user = new User();
    }

    public function loginForm()
    {
        if ($this->user->getActiveUser()) {
            redirect('admin/bookings');
            return;
        }

        $success = getFlash('success');
        $error   = getFlash('error');

        return view('admin.auth.login', [
            'success' => $success,
            'error'   => $error,
            'old'     => $_SESSION['old'] ?? [],
        ]);
    }

    public function login()
    {
        if ($this->user->getActiveUser()) {
            redirect('admin/bookings');
            return;
        }

        $data = $_POST;
        keepOld($data);

        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';

        if ($username === '' || $password === '') {
            setFlash('error', 'Vui lòng nhập tên đăng nhập và mật khẩu');
            redirect('admin/login');
            return;
        }

        $user = $this->user->verify($username, $password);
        if (!$user) {
            setFlash('error', 'Tên đăng nhập hoặc mật khẩu không đúng');
            redirect('admin/login');
            return;
        }

        if (($user['status'] ?? '') !== 'active') {
            setFlash('error', 'Tài khoản của bạn đã bị khóa, vui lòng liên hệ quản trị');
            redirect('admin/login');
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['auth_user_id'] = (int)$user['id'];
        $_SESSION['auth_username'] = $user['username'];
        $_SESSION['auth_role']     = $user['role'];
        $_SESSION['auth_full_name']= $user['full_name'];

        unset($_SESSION['old']);
        setFlash('success', 'Đăng nhập thành công, chào mừng ' . $user['full_name']);
        redirect('admin/bookings');
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        foreach (['auth_user_id', 'auth_username', 'auth_role', 'auth_full_name'] as $k) {
            if (isset($_SESSION[$k])) unset($_SESSION[$k]);
        }

        setFlash('success', 'Bạn đã đăng xuất khỏi hệ thống');
        redirect('admin/login');
    }
}
