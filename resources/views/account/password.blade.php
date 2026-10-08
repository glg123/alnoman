@extends('layouts.app')
@section('title', 'تغيير كلمة المرور')

@section('content')
    <div class="max-w-md">
        <h1 class="text-xl font-bold mb-6">تغيير كلمة المرور</h1>

        <form method="POST" action="{{ route('account.password.update') }}" class="bg-white border border-line rounded-2xl p-6 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-semibold mb-1.5">كلمة المرور الحالية</label>
                <input type="password" name="current_password" required autocomplete="current-password"
                       class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                @error('current_password') <p class="text-rejected text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1.5">كلمة المرور الجديدة</label>
                <input type="password" name="password" required autocomplete="new-password"
                       class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                <p class="text-xs text-muted mt-1">8 خانات على الأقل، فيها حروف وأرقام.</p>
                @error('password') <p class="text-rejected text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1.5">تأكيد كلمة المرور الجديدة</label>
                <input type="password" name="password_confirmation" required autocomplete="new-password"
                       class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
            </div>

            <button class="w-full bg-primary hover:bg-primary-dark transition text-white font-semibold rounded-lg py-2.5 text-sm">حفظ</button>
        </form>
    </div>
@endsection
