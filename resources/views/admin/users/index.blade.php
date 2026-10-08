@extends('layouts.app')
@section('title', 'المستخدمون')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-xl font-bold">المستخدمون</h1>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.import.create') }}"
               class="border border-line bg-white hover:bg-canvas transition font-semibold rounded-lg px-5 py-2.5 text-sm">
                استيراد من Excel
            </a>
            <a href="{{ route('admin.users.create') }}"
               class="bg-primary hover:bg-primary-dark transition text-white font-semibold rounded-lg px-5 py-2.5 text-sm">
                + إنشاء مستخدم جديد
            </a>
        </div>
    </div>

    <form method="GET" class="mb-5">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="بحث بالاسم أو اسم المستخدم..."
               class="w-full sm:w-72 rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
    </form>

    <div class="bg-white border border-line rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[760px]">
                <thead class="bg-canvas">
                    <tr class="text-right text-xs text-muted">
                        <th class="px-4 py-3 font-semibold">الاسم</th>
                        <th class="px-4 py-3 font-semibold">اسم المستخدم</th>
                        <th class="px-4 py-3 font-semibold">حالة البيانات</th>
                        <th class="px-4 py-3 font-semibold">الحساب</th>
                        <th class="px-4 py-3 font-semibold">تاريخ الإنشاء</th>
                        <th class="px-4 py-3 font-semibold">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr class="border-t border-line">
                            <td class="px-4 py-3 font-medium">{{ $user->name }}</td>
                            <td class="px-4 py-3 text-muted">{{ $user->username }}</td>
                            <td class="px-4 py-3">
                                @if($user->family)
                                    <x-status-badge :status="$user->family->status" />
                                @else
                                    <span class="text-xs text-muted">لم تُدخَل بعد</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($user->is_active)
                                    <span class="text-xs font-semibold text-approved">نشط</span>
                                @else
                                    <span class="text-xs font-semibold text-rejected">موقوف</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-muted">{{ $user->created_at->format('Y-m-d') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <form method="POST" action="{{ route('admin.users.reset-password', $user) }}"
                                          onsubmit="return confirm('بدك تولّد كلمة مرور جديدة لـ {{ $user->name }}؟ كلمة المرور القديمة رح تتعطل.');">
                                        @csrf
                                        <button class="text-xs font-semibold border border-line rounded-lg px-3 py-1.5 hover:bg-canvas transition">إعادة تعيين كلمة المرور</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.users.toggle-active', $user) }}"
                                          onsubmit="return confirm('{{ $user->is_active ? 'إيقاف حساب' : 'تفعيل حساب' }} {{ $user->name }}؟');">
                                        @csrf
                                        <button class="text-xs font-semibold border rounded-lg px-3 py-1.5 transition {{ $user->is_active ? 'border-rejected/40 text-rejected hover:bg-rejected/5' : 'border-approved/40 text-approved hover:bg-approved/5' }}">
                                            {{ $user->is_active ? 'إيقاف' : 'تفعيل' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-muted">لا يوجد مستخدمون.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $users->links() }}</div>
@endsection
