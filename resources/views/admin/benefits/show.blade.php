@extends('layouts.app')
@section('title', 'تفاصيل الاستفادة')

@section('content')
    <a href="{{ route('admin.benefits.index') }}" class="text-sm text-muted hover:text-ink mb-4 inline-block">← رجوع للسجل</a>

    <div class="bg-white border border-line rounded-2xl p-5 sm:p-6 mb-6">
        <div class="flex items-center gap-3 mb-4">
            @if($benefit->organization->logo_url)
                <img src="{{ $benefit->organization->logo_url }}" class="w-12 h-12 rounded-xl object-cover border border-line">
            @else
                <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary font-display font-bold text-lg">
                    {{ mb_substr($benefit->organization->name, 0, 1) }}
                </div>
            @endif
            <div>
                <h1 class="font-display text-lg font-bold">{{ $benefit->type }}</h1>
                <p class="text-sm text-muted">{{ $benefit->organization->name }} — {{ $benefit->distributed_at->format('Y-m-d') }}</p>
            </div>
        </div>
        @if($benefit->description)
            <p class="text-sm text-muted">{{ $benefit->description }}</p>
        @endif
        @if($benefit->creator)
            <p class="text-xs text-muted mt-3">سجّلها: {{ $benefit->creator->name }}</p>
        @endif
    </div>

    <div class="bg-white border border-line rounded-2xl overflow-hidden">
        <div class="px-5 py-3 border-b border-line">
            <h2 class="font-display font-bold text-sm">المستفيدون ({{ $benefit->families->count() }})</h2>
        </div>
        <div class="divide-y divide-line">
            @forelse($benefit->families as $family)
                <div class="px-5 py-3 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold">{{ $family->full_name }}</p>
                        <p class="text-xs text-muted">{{ $family->national_id }}</p>
                    </div>
                    <a href="{{ route('admin.families.show', $family) }}" class="text-xs font-semibold text-primary hover:text-primary-dark">عرض الأسرة</a>
                </div>
            @empty
                <p class="px-5 py-6 text-center text-muted text-sm">لا يوجد مستفيدون.</p>
            @endforelse
        </div>
    </div>
@endsection
