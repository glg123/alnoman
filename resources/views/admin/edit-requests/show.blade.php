@extends('layouts.app')
@section('title', 'مراجعة طلب التعديل')

@php
    $current = $editRequest->family;
    $proposed = $editRequest->payload;
    $fields = [
        'full_name' => 'الاسم الرباعي',
        'national_id' => 'رقم الهوية',
        'wife_name' => 'اسم الزوجة',
        'wife_national_id' => 'رقم هوية الزوجة',
        'members_count' => 'عدد الأفراد',
    ];
    $proposedPhotoUrl = ! empty($proposed['national_id_photo_path']) ? route('files.edit-request-photo', $editRequest) : null;
@endphp

@section('content')
    <a href="{{ route('admin.edit-requests.index') }}" class="text-sm text-muted hover:text-ink mb-4 inline-block">← رجوع للقائمة</a>

    <div class="mb-6">
        <h1 class="text-xl font-bold">طلب تعديل — {{ $current->full_name }}</h1>
        <p class="text-sm text-muted mt-1">مقدَّم من {{ $editRequest->user->name }} بتاريخ {{ $editRequest->created_at->format('Y-m-d H:i') }}</p>
    </div>

    <div class="bg-white border border-line rounded-2xl overflow-hidden mb-6">
        <table class="w-full text-sm">
            <thead class="bg-canvas">
                <tr class="text-right text-xs text-muted">
                    <th class="px-4 py-3 font-semibold">الحقل</th>
                    <th class="px-4 py-3 font-semibold">الحالي</th>
                    <th class="px-4 py-3 font-semibold">المقترح</th>
                </tr>
            </thead>
            <tbody>
                @foreach($fields as $key => $label)
                    @php $changed = (string) ($current->{$key} ?? '') !== (string) ($proposed[$key] ?? ''); @endphp
                    <tr class="border-t border-line {{ $changed ? 'bg-pending/5' : '' }}">
                        <td class="px-4 py-3 font-medium">{{ $label }}</td>
                        <td class="px-4 py-3 text-muted">{{ $current->{$key} ?: '—' }}</td>
                        <td class="px-4 py-3 {{ $changed ? 'font-semibold text-pending' : '' }}">{{ $proposed[$key] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($proposedPhotoUrl)
        <div class="mb-6">
            <a href="{{ $proposedPhotoUrl }}" target="_blank" class="text-sm font-semibold text-primary hover:text-primary-dark underline">عرض صورة الهوية المقترحة</a>
        </div>
    @endif

    <div class="grid sm:grid-cols-2 gap-4 mb-8">
        <div class="bg-white border border-line rounded-2xl p-5">
            <h2 class="font-bold text-sm mb-3">الأطفال — الحالي ({{ $current->children->count() }})</h2>
            @forelse($current->children as $child)
                <p class="text-sm text-muted py-1 border-b border-line last:border-0">{{ $child->full_name }} — {{ $child->age }} سنة</p>
            @empty
                <p class="text-sm text-muted">لا يوجد.</p>
            @endforelse
        </div>
        <div class="bg-white border border-line rounded-2xl p-5">
            <h2 class="font-bold text-sm mb-3">الأطفال — المقترح ({{ count($proposed['children'] ?? []) }})</h2>
            @forelse(($proposed['children'] ?? []) as $child)
                <p class="text-sm py-1 border-b border-line last:border-0">{{ $child['full_name'] ?? '—' }} — {{ $child['age'] ?? '—' }} سنة</p>
            @empty
                <p class="text-sm text-muted">لا يوجد.</p>
            @endforelse
        </div>
    </div>

    @if(!empty($proposed['special_cases']))
        <div class="bg-white border border-line rounded-2xl p-5 sm:p-6 mb-8">
            <h2 class="font-bold text-sm mb-3">حالات خاصة — المقترح ({{ count($proposed['special_cases']) }})</h2>
            <ul class="space-y-2">
                @foreach($proposed['special_cases'] as $caseIndex => $case)
                    <li class="text-sm border-b border-line last:border-0 pb-2">
                        <span class="font-semibold">{{ $case['type'] ?? '—' }}</span>
                        @if(!empty($case['description'])) <span class="text-muted"> — {{ $case['description'] }}</span> @endif
                        @if(!empty($case['document_path']))
                            <a href="{{ route('files.edit-request-case', [$editRequest, $caseIndex]) }}" target="_blank"
                               class="text-xs font-semibold text-primary hover:text-primary-dark underline block mt-0.5">عرض المرفق</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div>
        <div class="flex flex-col sm:flex-row gap-3">
            <form method="POST" action="{{ route('admin.edit-requests.approve', $editRequest) }}" class="sm:flex-1">
                @csrf
                <button type="submit" class="w-full bg-approved hover:opacity-90 transition text-white font-semibold rounded-lg py-2.5 text-sm">
                    اعتماد التعديل ودمجه
                </button>
            </form>

            <button type="button" onclick="document.getElementById('reject-box').classList.toggle('hidden')"
                    class="sm:flex-1 border border-rejected/30 text-rejected font-semibold rounded-lg py-2.5 text-sm hover:bg-rejected/5">
                رفض التعديل
            </button>
        </div>

        <div id="reject-box" class="hidden mt-4 bg-white border border-line rounded-2xl p-5">
            <form method="POST" action="{{ route('admin.edit-requests.reject', $editRequest) }}" class="space-y-3">
                @csrf
                <label class="block text-sm font-semibold">سبب الرفض</label>
                <textarea name="admin_note" rows="3" required
                          class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-rejected focus:border-rejected"></textarea>
                <button type="submit" class="bg-rejected hover:opacity-90 transition text-white font-semibold rounded-lg px-5 py-2 text-sm">
                    تأكيد الرفض
                </button>
            </form>
        </div>
    </div>
@endsection
