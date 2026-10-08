@extends('layouts.app')
@section('title', 'سجل النشاط')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-xl font-bold">سجل النشاط</h1>
        <form method="GET">
            <select name="action" onchange="this.form.submit()"
                    class="rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                <option value="">كل الأحداث</option>
                @foreach($actions as $key => $label)
                    <option value="{{ $key }}" {{ $action === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="bg-white border border-line rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[640px]">
                <thead class="bg-canvas">
                    <tr class="text-right text-xs text-muted">
                        <th class="px-4 py-3 font-semibold">الوقت</th>
                        <th class="px-4 py-3 font-semibold">المستخدم</th>
                        <th class="px-4 py-3 font-semibold">الحدث</th>
                        <th class="px-4 py-3 font-semibold">التفاصيل</th>
                        <th class="px-4 py-3 font-semibold">IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr class="border-t border-line">
                            <td class="px-4 py-3 text-muted whitespace-nowrap" dir="ltr">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3 font-medium">{{ $log->user?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs font-semibold">{{ $actions[$log->action] ?? $log->action }}</td>
                            <td class="px-4 py-3">{{ $log->description }}</td>
                            <td class="px-4 py-3 text-muted font-mono text-xs" dir="ltr">{{ $log->ip_address }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-muted">لا يوجد نشاط مسجّل بعد.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $logs->links() }}</div>
@endsection
