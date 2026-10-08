<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
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

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['username' => 'اسم المستخدم أو كلمة المرور غير صحيحة.'])->onlyInput('username');
        }

        $request->session()->regenerate();

        if (! Auth::user()->is_active) {
            Auth::logout();
            return back()->withErrors(['username' => 'هذا الحساب موقوف، راجع إدارة المخيم.']);
        }

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
