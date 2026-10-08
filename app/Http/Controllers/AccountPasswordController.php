<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AccountPasswordController extends Controller
{
    public function edit()
    {
        return view('account.password');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::min(8)->letters()->numbers(), 'different:current_password'],
        ], [
            'current_password.required'         => 'اكتب كلمة المرور الحالية.',
            'current_password.current_password' => 'كلمة المرور الحالية غير صحيحة.',
            'password.required'                 => 'اكتب كلمة المرور الجديدة.',
            'password.confirmed'                => 'تأكيد كلمة المرور غير مطابق.',
            'password.min'                      => 'كلمة المرور الجديدة لازم تكون 8 خانات على الأقل.',
            'password.letters'                  => 'كلمة المرور لازم تحتوي حروف.',
            'password.numbers'                  => 'كلمة المرور لازم تحتوي أرقام.',
            'password.different'                => 'كلمة المرور الجديدة لازم تختلف عن الحالية.',
        ]);

        Auth::user()->forceFill(['password' => Hash::make($data['password'])])->save();

        ActivityLog::record('account.password_changed', 'غيّر كلمة المرور الخاصة به');

        return back()->with('success', 'تم تغيير كلمة المرور.');
    }
}
