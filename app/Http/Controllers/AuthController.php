<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show the login page.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('replenishment.index');
        }

        return view('auth.login');
    }

    /**
     * Handle user authentication.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            \App\Models\ActivityLog::record(
                \App\Models\ActivityLog::ACTION_LOGIN,
                'Pengguna ' . Auth::user()->name . ' (' . Auth::user()->email . ') berhasil masuk ke sistem.',
                ['role' => Auth::user()->role],
                \App\Models\ActivityLog::LEVEL_SUCCESS,
                Auth::user()
            );

            return redirect()->intended(route('replenishment.index'))
                ->with('success', 'Selamat datang kembali, ' . Auth::user()->name . '!');
        }

        \App\Models\ActivityLog::record(
            \App\Models\ActivityLog::ACTION_LOGIN,
            'Percobaan login gagal untuk email: ' . $request->input('email'),
            ['email' => $request->input('email')],
            \App\Models\ActivityLog::LEVEL_WARNING
        );

        return back()->withErrors([
            'email' => 'Email atau password yang Anda masukkan salah.',
        ])->onlyInput('email');
    }

    /**
     * Log out the current user.
     */
    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user) {
            \App\Models\ActivityLog::record(
                \App\Models\ActivityLog::ACTION_LOGOUT,
                'Pengguna ' . $user->name . ' (' . $user->email . ') keluar dari sistem.',
                ['role' => $user->role],
                \App\Models\ActivityLog::LEVEL_INFO,
                $user
            );
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Anda telah berhasil keluar dari sistem.');
    }
}
