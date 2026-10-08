@extends('layouts.app')
@section('title', 'تم إنشاء الحساب')

@section('content')
    <div class="max-w-md">
        <div class="bg-approved/10 border border-approved/30 text-approved rounded-lg px-4 py-3 text-sm font-medium mb-6">
            تم إنشاء حساب "{{ $user->name }}" بنجاح.
        </div>

        <div class="bg-white border border-line rounded-2xl p-6">
            <h2 class="font-bold text-base mb-4">بيانات الدخول — انسخها وأرسلها للمستخدم</h2>

            <div class="space-y-3 mb-5">
                <div class="flex items-center justify-between bg-canvas rounded-lg px-4 py-3">
                    <span class="text-xs text-muted">اسم المستخدم</span>
                    <span class="font-mono font-semibold">{{ $user->username }}</span>
                </div>
                <div class="flex items-center justify-between bg-canvas rounded-lg px-4 py-3">
                    <span class="text-xs text-muted">كلمة المرور</span>
                    <span class="font-mono font-semibold">{{ $plainPassword }}</span>
                </div>
            </div>

            <button type="button" id="copy-btn"
                    onclick="navigator.clipboard.writeText('اسم المستخدم: {{ $user->username }}\nكلمة المرور: {{ $plainPassword }}').then(function () { var b = document.getElementById('copy-btn'); b.textContent = 'تم النسخ ✓'; setTimeout(function () { b.textContent = 'نسخ بيانات الدخول'; }, 2000); });"
                    class="w-full bg-primary hover:bg-primary-dark transition text-white font-semibold rounded-lg py-2.5 text-sm mb-3">
                نسخ بيانات الدخول
            </button>

            <a href="{{ route('admin.users.index') }}" class="block text-center text-sm font-semibold text-muted hover:text-ink">
                العودة لقائمة المستخدمين
            </a>
        </div>

        <p class="text-xs text-muted mt-4">لن تظهر كلمة المرور مرة أخرى بعد مغادرة هذه الصفحة — تأكد من نسخها الآن.</p>
    </div>
@endsection

