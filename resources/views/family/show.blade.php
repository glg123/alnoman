@extends('layouts.app')
@section('title', 'بيانات أسرتي')

@section('content')

    @if(!$family)
        <div class="bg-white border border-line rounded-2xl p-8 sm:p-12 text-center">
            <h1 class="text-lg font-bold mb-2">لم تُدخل بياناتك بعد</h1>
            <p class="text-sm text-muted mb-6 max-w-sm mx-auto">عبّئ بيانات أسرتك ليتم مراجعتها واعتمادها من إدارة المخيم.</p>
            <a href="{{ route('family.create') }}"
               class="inline-block bg-primary hover:bg-primary-dark transition text-white font-semibold rounded-lg px-6 py-2.5 text-sm">
                إدخال البيانات
            </a>
        </div>
    @else
        <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
            <div>
                <h1 class="text-xl font-bold">{{ $family->full_name }}</h1>
                <p class="text-sm text-muted mt-1">رقم الهوية: {{ $family->national_id }}</p>
                @if($family->national_id_photo_url)
                    <a href="{{ $family->national_id_photo_url }}" target="_blank" class="text-xs font-semibold text-primary hover:text-primary-dark underline">عرض صورة الهوية</a>
                @endif
            </div>
            <div class="flex items-center gap-3">
                <x-status-badge :status="$family->status" />
                <a href="{{ route('family.edit') }}" class="text-sm font-semibold text-primary hover:text-primary-dark">تعديل البيانات</a>
            </div>
        </div>

        @if($family->status === 'rejected' && $family->rejection_reason)
            <div class="mb-6 rounded-lg border border-rejected/30 bg-rejected/10 text-rejected px-4 py-3 text-sm">
                <span class="font-semibold">سبب الرفض:</span> {{ $family->rejection_reason }}
            </div>
        @endif

        @if($family->editRequests->where('status', 'pending')->isNotEmpty())
            <div class="mb-6 rounded-lg border border-pending/30 bg-pending/10 text-pending px-4 py-3 text-sm">
                لديك طلب تعديل قيد المراجعة حالياً.
            </div>
        @endif

        <div class="grid sm:grid-cols-2 gap-4 mb-6">
            <div class="bg-white border border-line rounded-2xl p-5">
                <p class="text-xs text-muted mb-1">اسم الزوجة</p>
                <p class="font-semibold">{{ $family->wife_name ?: '—' }}</p>
                <p class="text-xs text-muted mt-3 mb-1">رقم هوية الزوجة</p>
                <p class="font-semibold">{{ $family->wife_national_id ?: '—' }}</p>
            </div>
            <div class="bg-white border border-line rounded-2xl p-5">
                <p class="text-xs text-muted mb-1">عدد أفراد الأسرة</p>
                <p class="font-semibold text-2xl">{{ $family->members_count }}</p>
            </div>
        </div>

        <div class="bg-white border border-line rounded-2xl p-5 sm:p-6 mb-6">
            <h2 class="font-bold text-base mb-4">الأطفال ({{ $family->children->count() }})</h2>
            @if($family->children->isEmpty())
                <p class="text-sm text-muted">لا يوجد أطفال مسجلون.</p>
            @else
                <div class="overflow-x-auto -mx-2">
                    <table class="w-full text-sm min-w-[400px]">
                        <thead>
                            <tr class="text-right text-xs text-muted border-b border-line">
                                <th class="px-2 py-2 font-semibold">الاسم</th>
                                <th class="px-2 py-2 font-semibold">رقم الهوية</th>
                                <th class="px-2 py-2 font-semibold">العمر</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($family->children as $child)
                                <tr class="border-b border-line last:border-0">
                                    <td class="px-2 py-2.5">{{ $child->full_name }}</td>
                                    <td class="px-2 py-2.5 text-muted">{{ $child->national_id ?: '—' }}</td>
                                    <td class="px-2 py-2.5">{{ $child->age }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="bg-white border border-line rounded-2xl p-5 sm:p-6">
            <h2 class="font-bold text-base mb-4">حالات خاصة / إعاقات ({{ $family->specialCases->count() }})</h2>
            @if($family->specialCases->isEmpty())
                <p class="text-sm text-muted">لا توجد حالات مسجلة.</p>
            @else
                <ul class="space-y-3">
                    @foreach($family->specialCases as $case)
                        <li class="flex items-start gap-3 text-sm">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary mt-2 shrink-0"></span>
                            <div>
                                <p class="font-semibold">{{ $case->type }}</p>
                                @if($case->child)
                                    <p class="text-muted text-xs">تخص: {{ $case->child->full_name }}</p>
                                @endif
                                @if($case->description)
                                    <p class="text-muted text-xs mt-0.5">{{ $case->description }}</p>
                                @endif
                                @if($case->document_url)
                                    <a href="{{ $case->document_url }}" target="_blank" class="text-xs font-semibold text-primary hover:text-primary-dark underline block mt-1">عرض المرفق</a>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        @if($family->benefitDistributions->isNotEmpty())
            <div class="bg-white border border-line rounded-2xl p-5 sm:p-6 mt-6">
                <h2 class="font-bold text-base mb-4">الاستفادات المسجَّلة ({{ $family->benefitDistributions->count() }})</h2>
                <ul class="space-y-3">
                    @foreach($family->benefitDistributions as $dist)
                        <li class="flex items-center gap-3 text-sm">
                            @if($dist->organization->logo_url)
                                <img src="{{ $dist->organization->logo_url }}" class="w-8 h-8 rounded-lg object-cover border border-line shrink-0">
                            @else
                                <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary font-display font-bold text-xs shrink-0">
                                    {{ mb_substr($dist->organization->name, 0, 1) }}
                                </div>
                            @endif
                            <div>
                                <p class="font-semibold">{{ $dist->type }}</p>
                                <p class="text-muted text-xs">{{ $dist->organization->name }} — {{ $dist->distributed_at->format('Y-m-d') }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endif

@endsection
