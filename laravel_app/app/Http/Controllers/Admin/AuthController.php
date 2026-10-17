<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function loginForm(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect('/admin');
        }

        return view('admin.auth.login', [
            'error' => session('error') ?? $request->session()->get('errors')?->first(),
            'old' => ['username' => old('username', '')],
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt([
            'username' => trim($credentials['username']),
            'password' => $credentials['password'],
            'status' => 'active',
        ])) {
            return back()->withInput($request->only('username'))
                ->with('error', 'Tên đăng nhập hoặc mật khẩu không đúng, hoặc tài khoản đã bị khóa.');
        }

        $request->session()->regenerate();

        return redirect()->intended('/admin');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/admin/login');
    }
}
