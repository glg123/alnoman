@extends('layouts.app')
@section('title', 'مراجعة بيانات الأسرة')

@section('content')
    <a href="{{ route('admin.families.index') }}" class="text-sm text-muted hover:text-ink mb-4 inline-block">← رجوع للقائمة</a>

    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold">{{ $family->full_name }}</h1>
            <p class="text-sm text-muted mt-1">مقدَّم من: {{ $family->user->name }} ({{ $family->user->username }})</p>
            @if($family->national_id_photo_url)
                <a href="{{ $family->national_id_photo_url }}" target="_blank" class="text-xs font-semibold text-primary hover:text-primary-dark underline">عرض صورة الهوية</a>
            @endif
        </div>
        <x-status-badge :status="$family->status" />
    </div>

    @if($family->status === 'rejected' && $family->rejection_reason)
        <div class="mb-6 rounded-lg border border-rejected/30 bg-rejected/10 text-rejected px-4 py-3 text-sm">
            <span class="font-semibold">سبب الرفض:</span> {{ $family->rejection_reason }}
        </div>
    @endif

    <div class="grid sm:grid-cols-2 gap-4 mb-6">
        <div class="bg-white border border-line rounded-2xl p-5">
            <p class="text-xs text-muted mb-1">رقم الهوية</p>
            <p class="font-semibold">{{ $family->national_id }}</p>
        </div>
        <div class="bg-white border border-line rounded-2xl p-5">
            <p class="text-xs text-muted mb-1">عدد أفراد الأسرة</p>
            <p class="font-semibold">{{ $family->members_count }}</p>
        </div>
        <div class="bg-white border border-line rounded-2xl p-5">
            <p class="text-xs text-muted mb-1">اسم الزوجة</p>
            <p class="font-semibold">{{ $family->wife_name ?: '—' }}</p>
        </div>
        <div class="bg-white border border-line rounded-2xl p-5">
            <p class="text-xs text-muted mb-1">رقم هوية الزوجة</p>
            <p class="font-semibold">{{ $family->wife_national_id ?: '—' }}</p>
        </div>
    </div>

    <div class="bg-white border border-line rounded-2xl p-5 sm:p-6 mb-6">
        <h2 class="font-bold text-base mb-4">الأطفال ({{ $family->children->count() }})</h2>
        @if($family->children->isEmpty())
            <p class="text-sm text-muted">لا يوجد أطفال مسجلون.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[400px]">
                    <thead>
                        <tr class="text-right text-xs text-muted border-b border-line">
                            <th class="py-2 font-semibold">الاسم</th>
                            <th class="py-2 font-semibold">رقم الهوية</th>
                            <th class="py-2 font-semibold">العمر</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($family->children as $child)
                            <tr class="border-b border-line last:border-0">
                                <td class="py-2.5">{{ $child->full_name }}</td>
                                <td class="py-2.5 text-muted">{{ $child->national_id ?: '—' }}</td>
                                <td class="py-2.5">{{ $child->age }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="bg-white border border-line rounded-2xl p-5 sm:p-6 mb-8">
        <h2 class="font-bold text-base mb-4">حالات خاصة / إعاقات ({{ $family->specialCases->count() }})</h2>
        @if($family->specialCases->isEmpty())
            <p class="text-sm text-muted">لا توجد حالات مسجلة.</p>
        @else
            <ul class="space-y-3">
                @foreach($family->specialCases as $case)
                    <li class="text-sm">
                        <span class="font-semibold">{{ $case->type }}</span>
                        @if($case->child) <span class="text-muted"> — تخص {{ $case->child->full_name }}</span> @endif
                        @if($case->description) <p class="text-muted text-xs mt-0.5">{{ $case->description }}</p> @endif
                        @if($case->document_url)
                            <a href="{{ $case->document_url }}" target="_blank" class="text-xs font-semibold text-primary hover:text-primary-dark underline block mt-1">عرض المرفق</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    @if($family->benefitDistributions->isNotEmpty())
        <div class="bg-white border border-line rounded-2xl p-5 sm:p-6 mb-8">
            <h2 class="font-bold text-base mb-4">الاستفادات المسجَّلة ({{ $family->benefitDistributions->count() }})</h2>
            <ul class="space-y-2">
                @foreach($family->benefitDistributions as $dist)
                    <li class="text-sm">
                        <span class="font-semibold">{{ $dist->type }}</span>
                        <span class="text-muted"> — {{ $dist->organization->name }} — {{ $dist->distributed_at->format('Y-m-d') }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($family->status === 'pending')
        <div>
            <div class="flex flex-col sm:flex-row gap-3">
                <form method="POST" action="{{ route('admin.families.approve', $family) }}" class="sm:flex-1">
                    @csrf
                    <button type="submit" class="w-full bg-approved hover:opacity-90 transition text-white font-semibold rounded-lg py-2.5 text-sm">
                        اعتماد البيانات
                    </button>
                </form>

                <button type="button" onclick="document.getElementById('reject-box').classList.toggle('hidden')"
                        class="sm:flex-1 border border-rejected/30 text-rejected font-semibold rounded-lg py-2.5 text-sm hover:bg-rejected/5">
                    رفض البيانات
                </button>
            </div>

            <div id="reject-box" class="hidden mt-4 bg-white border border-line rounded-2xl p-5">
                <form method="POST" action="{{ route('admin.families.reject', $family) }}" class="space-y-3">
                    @csrf
                    <label class="block text-sm font-semibold">سبب الرفض</label>
                    <textarea name="rejection_reason" rows="3" required
                              class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-rejected focus:border-rejected"></textarea>
                    <button type="submit" class="bg-rejected hover:opacity-90 transition text-white font-semibold rounded-lg px-5 py-2 text-sm">
                        تأكيد الرفض
                    </button>
                </form>
            </div>
        </div>
    @endif
@endsection
