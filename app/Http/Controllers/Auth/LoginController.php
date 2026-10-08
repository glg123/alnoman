<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    private const MAX_ATTEMPTS = 5;
    private const LOCK_SECONDS = 120;

    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $key = Str::lower($credentials['username']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);

            return back()
                ->withErrors(['username' => "محاولات كثيرة. حاول مرة ثانية بعد {$seconds} ثانية."])
                ->onlyInput('username');
        }

        // الحساب الموقوف ما يدخل أبداً (قبل أي تسجيل دخول)، وبنعطيه رسالة واضحة إن كانت بياناته صحيحة.
        $user = User::where('username', $credentials['username'])->first();
        if ($user && ! $user->is_active && Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($key, self::LOCK_SECONDS);

            return back()->withErrors(['username' => 'هذا الحساب موقوف، راجع إدارة المخيم.'])->onlyInput('username');
        }

        if (! Auth::attempt($credentials + ['is_active' => true], $request->boolean('remember'))) {
            RateLimiter::hit($key, self::LOCK_SECONDS);

            return back()->withErrors(['username' => 'اسم المستخدم أو كلمة المرور غير صحيحة.'])->onlyInput('username');
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        ActivityLog::record('auth.login', 'سجّل الدخول');

        return Auth::user()->isAdmin()
            ? redirect()->intended(route('admin.families.index'))
            : redirect()->intended(route('family.show'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
