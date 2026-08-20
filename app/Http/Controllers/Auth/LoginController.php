<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            if (! $user->is_active) {
                Auth::logout();

                return back()->withErrors([
                    'email' => 'Your account has been deactivated. Contact an administrator.',
                ]);
            }

            $request->session()->regenerate();
            $user->update(['last_activity_at' => now()]);

            AuditLog::log([
                'action' => 'login',
                'description' => "User {$user->name} logged in",
            ]);

            if ($user->must_reset_password) {
                return redirect()->route('password.change');
            }

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        AuditLog::log([
            'action' => 'logout',
            'description' => 'User '.Auth::user()?->name.' logged out',
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
