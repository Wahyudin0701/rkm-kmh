<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Tampilkan halaman login sistem.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        return view('auth.login');
    }

    /**
     * Proses autentikasi pengguna menggunakan username & password.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'Username atau email wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $remember = $request->boolean('remember');
        if ($remember) {
            Auth::guard('web')->setRememberDuration(43200); // 30 hari (30 * 24 * 60 menit)
        }
        $loginField = filter_var($credentials['username'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (Auth::attempt([$loginField => $credentials['username'], 'password' => $credentials['password']], $remember)) {
            $request->session()->regenerate();

            /** @var \App\Models\User $user */
            $user = Auth::user();

            if ($request->session()->has('url.intended')) {
                return redirect()->intended()
                    ->with('success', "Selamat datang kembali, {$user->name}!");
            }

            return $this->redirectBasedOnRole($user)
                ->with('success', "Selamat datang kembali, {$user->name}!");
        }

        return back()->withErrors([
            'username' => 'Username atau password yang Anda masukkan salah.',
        ])->onlyInput('username');
    }

    /**
     * Logout pengguna dari sistem.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil logout.');
    }

    /**
     * Redirect cerdas berdasarkan role pengguna setelah login.
     */
    private function redirectBasedOnRole($user): RedirectResponse
    {
        if ($user->isSuperAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->role === 'employee') {
            if ($user->can('employee.dashboard')) {
                return redirect()->route('employee.dashboard');
            } elseif ($user->can('employee.p2h')) {
                return redirect()->route('p2h.index');
            } elseif ($user->can('employee.timesheet')) {
                return redirect()->route('timesheet.index');
            } else {
                return redirect()->route('profile.index');
            }
        }

        return match ($user->role) {
            'super_admin', 'superadmin', 'admin' => redirect()->route('admin.dashboard'),
            'hse_admin' => redirect()->route('hse.dashboard'),
            'qma_admin' => redirect()->route('qma.dashboard'),
            'pg_admin' => redirect()->route('pg.dashboard'),
            'gate_officer' => redirect()->route('pg.gate.inspection'),
            default => redirect()->route('profile.index'),
        };
    }
}
