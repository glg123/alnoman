@extends('layouts.app')
@section('title', 'طلبات الأسر')

@section('content')
    <h1 class="font-display text-xl font-bold mb-6">طلبات الأسر</h1>

    {{-- بطاقات إحصائية --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        <div class="bg-white border border-line rounded-2xl p-4">
            <p class="text-xs text-muted mb-1">بانتظار المراجعة</p>
            <p class="font-display text-2xl font-bold text-pending">{{ $stats['pending'] }}</p>
        </div>
        <div class="bg-white border border-line rounded-2xl p-4">
            <p class="text-xs text-muted mb-1">معتمدة</p>
            <p class="font-display text-2xl font-bold text-approved">{{ $stats['approved'] }}</p>
        </div>
        <div class="bg-white border border-line rounded-2xl p-4">
            <p class="text-xs text-muted mb-1">مرفوضة</p>
            <p class="font-display text-2xl font-bold text-rejected">{{ $stats['rejected'] }}</p>
        </div>
        <div class="bg-white border border-line rounded-2xl p-4">
            <p class="text-xs text-muted mb-1">إجمالي الأفراد المعتمدين</p>
            <p class="font-display text-2xl font-bold text-primary">{{ $stats['members'] }}</p>
        </div>
    </div>

    <div class="flex gap-2 mb-5 overflow-x-auto">
        @foreach(['pending' => 'بانتظار المراجعة', 'approved' => 'معتمدة', 'rejected' => 'مرفوضة', 'all' => 'الكل'] as $key => $label)
            <a href="{{ route('admin.families.index', ['status' => $key]) }}"
               class="shrink-0 px-4 py-2 rounded-full text-sm font-semibold border {{ $status === $key ? 'bg-primary text-white border-primary' : 'bg-white text-muted border-line hover:border-primary/40' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- جدول — سطح المكتب --}}
    <div class="hidden sm:block bg-white border border-line rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[600px]">
                <thead class="bg-canvas">
                    <tr class="text-right text-xs text-muted">
                        <th class="px-4 py-3 font-semibold">الاسم الرباعي</th>
                        <th class="px-4 py-3 font-semibold">رقم الهوية</th>
                        <th class="px-4 py-3 font-semibold">عدد الأفراد</th>
                        <th class="px-4 py-3 font-semibold">الحالة</th>
                        <th class="px-4 py-3 font-semibold"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($families as $family)
                        <tr class="border-t border-line">
                            <td class="px-4 py-3 font-medium">{{ $family->full_name }}</td>
                            <td class="px-4 py-3 text-muted">{{ $family->national_id }}</td>
                            <td class="px-4 py-3">{{ $family->members_count }}</td>
                            <td class="px-4 py-3"><x-status-badge :status="$family->status" /></td>
                            <td class="px-4 py-3 text-left">
                                <a href="{{ route('admin.families.show', $family) }}" class="text-primary font-semibold hover:text-primary-dark">مراجعة</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-muted">لا توجد طلبات ضمن هذا التصنيف.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- بطاقات — موبايل --}}
    <div class="sm:hidden space-y-3">
        @forelse($families as $family)
            <a href="{{ route('admin.families.show', $family) }}" class="block bg-white border border-line rounded-2xl p-4">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <p class="font-semibold">{{ $family->full_name }}</p>
                    <x-status-badge :status="$family->status" />
                </div>
                <p class="text-xs text-muted">رقم الهوية: {{ $family->national_id }}</p>
                <p class="text-xs text-muted">عدد الأفراد: {{ $family->members_count }}</p>
            </a>
        @empty
            <p class="text-center text-muted bg-white border border-line rounded-2xl py-8">لا توجد طلبات ضمن هذا التصنيف.</p>
        @endforelse
    </div>

    <div class="mt-5">{{ $families->links() }}</div>
@endsection
