<?php

namespace App\Controllers\Admin;

use App\Controller;
use App\Models\User;

class AuthController extends Controller
{
    protected $user;

    protected function redirectForUser($user)
    {
        $role = $user['role'] ?? '';

        if ($role === 'ADMIN') {
            redirect('admin/bookings');
        }

        if ($role === 'OWNER') {
            redirect('owner/dashboard');
        }

        redirect('');
    }

    public function __construct()
    {
        $this->user = new User();
    }

    public function loginForm()
    {
        if ($this->user->getActiveUser()) {
            $this->redirectForUser($this->user->getActiveUser());
            return;
        }

        $success = getFlash('success');
        $error   = getFlash('error');

        return view('admin.auth.login', [
            'success' => $success,
            'error'   => $error,
            'old'     => $_SESSION['old'] ?? [],
            'csrfToken' => csrfToken(),
        ]);
    }

    public function registerForm()
    {
        if ($this->user->getActiveUser()) {
            $this->redirectForUser($this->user->getActiveUser());
            return;
        }

        return view('admin.auth.register', [
            'success' => getFlash('success'),
            'error' => getFlash('error'),
            'old' => $_SESSION['old'] ?? [],
            'csrfToken' => csrfToken(),
        ]);
    }

    public function register()
    {
        if ($this->user->getActiveUser()) {
            $this->redirectForUser($this->user->getActiveUser());
            return;
        }

        if (!isValidCsrfToken($_POST['_csrf_token'] ?? null)) {
            setFlash('error', 'Phiên đăng ký không hợp lệ hoặc đã hết hạn. Vui lòng thử lại.');
            redirect('admin/register');
            return;
        }

        $data = $_POST;
        keepOld([
            'username' => trim($data['username'] ?? ''),
            'full_name' => trim($data['full_name'] ?? ''),
            'phone' => trim($data['phone'] ?? ''),
        ]);

        $username = trim($data['username'] ?? '');
        $fullName = trim($data['full_name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $password = $data['password'] ?? '';
        $passwordConfirm = $data['password_confirmation'] ?? '';

        if (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $username)) {
            setFlash('error', 'Tên đăng nhập phải có 3–30 ký tự, chỉ gồm chữ không dấu, số, dấu chấm, gạch dưới hoặc gạch ngang.');
            redirect('admin/register');
            return;
        }
        if ($fullName === '' || mb_strlen($fullName) > 100) {
            setFlash('error', 'Vui lòng nhập họ tên không quá 100 ký tự.');
            redirect('admin/register');
            return;
        }
        if ($phone !== '' && mb_strlen($phone) > 20) {
            setFlash('error', 'Số điện thoại không được vượt quá 20 ký tự.');
            redirect('admin/register');
            return;
        }
        if (strlen($password) < 8) {
            setFlash('error', 'Mật khẩu phải có ít nhất 8 ký tự.');
            redirect('admin/register');
            return;
        }
        if ($password !== $passwordConfirm) {
            setFlash('error', 'Mật khẩu xác nhận không khớp.');
            redirect('admin/register');
            return;
        }
        if ($this->user->usernameExists($username)) {
            setFlash('error', 'Tên đăng nhập đã được sử dụng.');
            redirect('admin/register');
            return;
        }

        try {
            $this->user->register($username, $password, $fullName, $phone);
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            setFlash('error', 'Tên đăng nhập đã được sử dụng.');
            redirect('admin/register');
            return;
        }

        unset($_SESSION['old']);
        $user = $this->user->findByUsername($username);
        if (!$user || ($user['status'] ?? '') !== 'active') {
            setFlash('error', 'Không thể kích hoạt tài khoản sau khi đăng ký. Vui lòng đăng nhập lại.');
            redirect('login');
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_regenerate_id(true);
        $_SESSION['auth_user_id'] = (int)$user['id'];
        $_SESSION['auth_username'] = $user['username'];
        $_SESSION['auth_role'] = $user['role'];
        $_SESSION['auth_full_name'] = $user['full_name'];

        unset($_SESSION['old']);
        setFlash('success', 'Đăng ký thành công. Bạn đã đăng nhập với quyền USER.');
        redirect('/');
    }

    public function login()
    {
        if ($this->user->getActiveUser()) {
            $this->redirectForUser($this->user->getActiveUser());
            return;
        }

        if (!isValidCsrfToken($_POST['_csrf_token'] ?? null)) {
            setFlash('error', 'Phiên đăng nhập không hợp lệ hoặc đã hết hạn. Vui lòng thử lại.');
            redirect('admin/login');
            return;
        }

        $data = $_POST;
        keepOld(['username' => trim($data['username'] ?? '')]);

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
            $message = ($user['status'] ?? '') === 'pending'
                ? 'Tài khoản chưa được kích hoạt. Vui lòng đăng ký lại hoặc liên hệ quản trị.'
                : 'Tài khoản của bạn đã bị khóa, vui lòng liên hệ quản trị.';
            setFlash('error', $message);
            redirect('admin/login');
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_regenerate_id(true);
        $_SESSION['auth_user_id'] = (int)$user['id'];
        $_SESSION['auth_username'] = $user['username'];
        $_SESSION['auth_role']     = $user['role'];
        $_SESSION['auth_full_name']= $user['full_name'];

        unset($_SESSION['old']);
        setFlash('success', 'Đăng nhập thành công.');
        $this->redirectForUser($user);
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST'
            && !isValidCsrfToken($_POST['_csrf_token'] ?? null)) {
            setFlash('error', 'Phiên đăng xuất không hợp lệ hoặc đã hết hạn.');
            redirect('login');
            return;
        }

        foreach (['auth_user_id', 'auth_username', 'auth_role', 'auth_full_name'] as $k) {
            if (isset($_SESSION[$k])) unset($_SESSION[$k]);
        }

        setFlash('success', 'Bạn đã đăng xuất khỏi hệ thống');
        redirect('login');
    }
}
