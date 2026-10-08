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
            <table class="w-full text-sm min-w-[600px]">
                <thead class="bg-canvas">
                    <tr class="text-right text-xs text-muted">
                        <th class="px-4 py-3 font-semibold">الاسم</th>
                        <th class="px-4 py-3 font-semibold">اسم المستخدم</th>
                        <th class="px-4 py-3 font-semibold">حالة البيانات</th>
                        <th class="px-4 py-3 font-semibold">تاريخ الإنشاء</th>
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
                            <td class="px-4 py-3 text-muted">{{ $user->created_at->format('Y-m-d') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-muted">لا يوجد مستخدمون.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $users->links() }}</div>
@endsection
