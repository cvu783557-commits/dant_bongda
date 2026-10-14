<?php

namespace App\Controllers\Admin;

use App\Controller;
use App\Models\User;

class AuthController extends Controller
{
    private User $user;

    public function __construct()
    {
        $this->user = new User();
    }

    public function loginForm()
    {
        if (!empty($_SESSION['auth_user_id'])) {
            redirect('admin');
        }

        return view('admin.auth.login', [
            'error' => getFlash('error'),
            'old' => $_SESSION['old'] ?? [],
        ]);
    }

    public function login()
    {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $_SESSION['old'] = ['username' => $username];

        if ($username === '' || $password === '') {
            setFlash('error', 'Vui lòng nhập tên đăng nhập và mật khẩu.');
            redirect('admin/login');
        }

        $user = $this->user->verify($username, $password);
        if (!$user) {
            setFlash('error', 'Tên đăng nhập hoặc mật khẩu không đúng, hoặc tài khoản đã bị khóa.');
            redirect('admin/login');
        }

        session_regenerate_id(true);
        $_SESSION['auth_user_id'] = (int)$user['id'];
        $_SESSION['auth_username'] = $user['username'];
        $_SESSION['auth_role'] = $user['role'];
        $_SESSION['auth_full_name'] = $user['full_name'];
        unset($_SESSION['old']);

        redirect('admin');
    }

    public function logout()
    {
        foreach (['auth_user_id', 'auth_username', 'auth_role', 'auth_full_name'] as $key) {
            unset($_SESSION[$key]);
        }

        session_regenerate_id(true);
        redirect('admin/login');
    }
}
