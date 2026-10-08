@extends('layouts.app')
@section('title', 'طلبات التعديل')

@section('content')
    <h1 class="text-xl font-bold mb-6">طلبات التعديل</h1>

    <div class="bg-white border border-line rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[550px]">
                <thead class="bg-canvas">
                    <tr class="text-right text-xs text-muted">
                        <th class="px-4 py-3 font-semibold">الأسرة</th>
                        <th class="px-4 py-3 font-semibold">مقدَّم من</th>
                        <th class="px-4 py-3 font-semibold">تاريخ الطلب</th>
                        <th class="px-4 py-3 font-semibold"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($editRequests as $req)
                        <tr class="border-t border-line">
                            <td class="px-4 py-3 font-medium">{{ $req->family->full_name }}</td>
                            <td class="px-4 py-3 text-muted">{{ $req->user->name }}</td>
                            <td class="px-4 py-3 text-muted">{{ $req->created_at->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3 text-left">
                                <a href="{{ route('admin.edit-requests.show', $req) }}" class="text-primary font-semibold hover:text-primary-dark">مراجعة</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-muted">لا توجد طلبات تعديل بانتظار المراجعة.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $editRequests->links() }}</div>
@endsection
