<?php

namespace App\Controllers;

use App\Controller;
use App\Models\User;

class OwnerController extends Controller
{
    protected User $user;

    public function __construct()
    {
        $this->user = new User();
    }

    protected function requireOwner(): ?array
    {
        $currentUser = $this->user->getActiveUser();

        if (!$currentUser || ($currentUser['role'] ?? '') !== 'OWNER') {
            setFlash('error', 'Vui lòng đăng nhập bằng tài khoản chủ sân.');
            redirect('owner/login');
        }

        return $currentUser;
    }

    public function loginForm()
    {
        if ($this->user->getActiveUser()) {
            $role = $this->user->getActiveUser()['role'] ?? '';
            if ($role === 'ADMIN') {
                redirect('admin/bookings');
            }
            if ($role === 'OWNER') {
                redirect('owner/dashboard');
            }
            redirect('/');
        }

        return view('owner.auth.login', [
            'success' => getFlash('success'),
            'error' => getFlash('error'),
            'old' => $_SESSION['old'] ?? [],
            'csrfToken' => csrfToken(),
        ]);
    }

    public function registerForm()
    {
        if ($this->user->getActiveUser()) {
            $role = $this->user->getActiveUser()['role'] ?? '';
            if ($role === 'ADMIN') {
                redirect('admin/bookings');
            }
            if ($role === 'OWNER') {
                redirect('owner/dashboard');
            }
            redirect('/');
        }

        return view('owner.auth.register', [
            'success' => getFlash('success'),
            'error' => getFlash('error'),
            'old' => $_SESSION['old'] ?? [],
            'csrfToken' => csrfToken(),
        ]);
    }

    public function register()
    {
        if ($this->user->getActiveUser()) {
            redirect('owner/dashboard');
        }

        if (!isValidCsrfToken($_POST['_csrf_token'] ?? null)) {
            setFlash('error', 'Phiên đăng ký không hợp lệ hoặc đã hết hạn. Vui lòng thử lại.');
            redirect('owner/register');
            return;
        }

        $data = $_POST;
        keepOld([
            'username' => trim($data['username'] ?? ''),
            'full_name' => trim($data['full_name'] ?? ''),
            'phone' => trim($data['phone'] ?? ''),
            'business_name' => trim($data['business_name'] ?? ''),
            'business_phone' => trim($data['business_phone'] ?? ''),
            'business_address' => trim($data['business_address'] ?? ''),
            'tax_code' => trim($data['tax_code'] ?? ''),
            'description' => trim($data['description'] ?? ''),
        ]);

        $username = trim($data['username'] ?? '');
        $fullName = trim($data['full_name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $businessName = trim($data['business_name'] ?? '');
        $businessPhone = trim($data['business_phone'] ?? '');
        $businessAddress = trim($data['business_address'] ?? '');
        $taxCode = trim($data['tax_code'] ?? '');
        $description = trim($data['description'] ?? '');
        $password = $data['password'] ?? '';
        $passwordConfirm = $data['password_confirmation'] ?? '';

        if (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $username)) {
            setFlash('error', 'Tên đăng nhập phải có 3–30 ký tự, chỉ gồm chữ không dấu, số, dấu chấm, gạch dưới hoặc gạch ngang.');
            redirect('owner/register');
            return;
        }

        if ($fullName === '' || mb_strlen($fullName) > 100) {
            setFlash('error', 'Vui lòng nhập họ tên chủ sân hợp lệ.');
            redirect('owner/register');
            return;
        }

        if ($phone === '' || mb_strlen($phone) > 20) {
            setFlash('error', 'Vui lòng nhập số điện thoại liên hệ cho doanh nghiệp/chủ sân.');
            redirect('owner/register');
            return;
        }

        if ($businessName === '' || mb_strlen($businessName) > 150) {
            setFlash('error', 'Vui lòng nhập tên doanh nghiệp hợp lệ.');
            redirect('owner/register');
            return;
        }

        if ($businessPhone === '' || mb_strlen($businessPhone) > 20) {
            setFlash('error', 'Vui lòng nhập số điện thoại doanh nghiệp.');
            redirect('owner/register');
            return;
        }

        if ($businessAddress === '' || mb_strlen($businessAddress) > 255) {
            setFlash('error', 'Vui lòng nhập địa chỉ kinh doanh.');
            redirect('owner/register');
            return;
        }

        if (strlen($password) < 8) {
            setFlash('error', 'Mật khẩu phải có ít nhất 8 ký tự.');
            redirect('owner/register');
            return;
        }

        if ($password !== $passwordConfirm) {
            setFlash('error', 'Mật khẩu xác nhận không khớp.');
            redirect('owner/register');
            return;
        }

        if ($this->user->usernameExists($username)) {
            setFlash('error', 'Tên đăng nhập đã được sử dụng.');
            redirect('owner/register');
            return;
        }

        $userId = $this->user->registerOwner($username, $password, $fullName, $phone);

        $ownerProfile = new \App\Models\OwnerProfile();
        $ownerProfile->saveProfile($userId, [
            'business_name' => $businessName,
            'business_phone' => $businessPhone,
            'business_address' => $businessAddress,
            'tax_code' => $taxCode,
            'description' => $description,
        ]);

        unset($_SESSION['old']);
        setFlash('success', 'Đăng ký chủ sân thành công. Tài khoản đang chờ admin duyệt.');
        redirect('owner/login');
    }

    public function login()
    {
        if ($this->user->getActiveUser()) {
            $role = $this->user->getActiveUser()['role'] ?? '';
            if ($role === 'ADMIN') {
                redirect('admin/bookings');
            }
            if ($role === 'OWNER') {
                redirect('owner/dashboard');
            }
            redirect('/');
        }

        if (!isValidCsrfToken($_POST['_csrf_token'] ?? null)) {
            setFlash('error', 'Phiên đăng nhập không hợp lệ hoặc đã hết hạn.');
            redirect('owner/login');
            return;
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            setFlash('error', 'Vui lòng nhập tên đăng nhập và mật khẩu.');
            redirect('owner/login');
            return;
        }

        $user = $this->user->verify($username, $password);
        if (!$user || ($user['role'] ?? '') !== 'OWNER') {
            setFlash('error', 'Tài khoản không tồn tại hoặc không thuộc quyền chủ sân.');
            redirect('owner/login');
            return;
        }

        if (($user['status'] ?? '') !== 'active') {
            setFlash('error', 'Tài khoản chủ sân đang chờ duyệt hoặc đã bị khóa.');
            redirect('owner/login');
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

        setFlash('success', 'Đăng nhập thành công.');
        redirect('owner/dashboard');
    }

    public function dashboard()
    {
        $currentUser = $this->requireOwner();

        return view('owner.dashboard', [
            'currentUser' => $currentUser,
            'todayBookings' => 0,
            'activePitches' => 0,
            'monthlyRevenue' => 0,
        ]);
    }

    public function pitches()
    {
        $currentUser = $this->requireOwner();

        return view('owner.pitches.index', [
            'currentUser' => $currentUser,
            'pitches' => [],
        ]);
    }

    public function bookings()
    {
        $currentUser = $this->requireOwner();

        return view('owner.bookings.index', [
            'currentUser' => $currentUser,
            'bookings' => [],
        ]);
    }

    public function revenue()
    {
        $currentUser = $this->requireOwner();

        return view('owner.revenue.index', [
            'currentUser' => $currentUser,
            'summary' => [
                'day' => 0,
                'week' => 0,
                'month' => 0,
            ],
        ]);
    }
}
