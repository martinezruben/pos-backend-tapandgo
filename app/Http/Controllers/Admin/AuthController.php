<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemParameter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function create()
    {
        $adminUserCount = \App\Models\AdminUser::query()->where('is_active', true)->count();
        $dbStatus = $adminUserCount > 0 ? 'OK' : 'EMPTY';

        return view('admin.auth.login', compact('dbStatus'));
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $params = SystemParameter::query()->first();
        $maxAttempts = $params !== null ? max(1, (int) $params->admin_max_failed_login_attempts) : 5;
        $lockoutMinutes = $params !== null ? max(1, (int) $params->admin_lockout_minutes) : 15;

        $throttleKey = 'admin-login:'.strtolower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'email' => 'Demasiados intentos fallidos. Podrá volver a intentarlo en '.$seconds.' segundos.',
            ])->onlyInput('email');
        }

        if (Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            return redirect()->intended(route('admin.dashboard'));
        }

        RateLimiter::hit($throttleKey, $lockoutMinutes * 60);

        return back()->withErrors(['email' => 'Credenciales inválidas.'])->onlyInput('email');
    }

    public function destroy(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
