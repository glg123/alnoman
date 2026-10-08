@extends('layouts.app')
@section('title', 'إنشاء مستخدم جديد')

@section('content')
    <div class="max-w-md">
        <h1 class="text-xl font-bold mb-1">إنشاء مستخدم جديد</h1>
        <p class="text-sm text-muted mb-6">سيتم توليد اسم مستخدم وكلمة مرور تلقائياً لإرسالهما للمستخدم.</p>

        <form method="POST" action="{{ route('admin.users.store') }}" class="bg-white border border-line rounded-2xl p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-semibold mb-1.5">الاسم الكامل</label>
                <input type="text" name="name" value="{{ old('name') }}" required autofocus
                       class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                @error('name') <p class="text-rejected text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <button type="submit"
                    class="w-full bg-primary hover:bg-primary-dark transition text-white font-semibold rounded-lg py-2.5 text-sm">
                إنشاء الحساب
            </button>
        </form>
    </div>
@endsection
