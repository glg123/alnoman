@extends('layouts.app')
@section('title', 'سجل الاستفادات')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="font-display text-xl font-bold">سجل الاستفادات</h1>
        <a href="{{ route('admin.benefits.create') }}"
           class="bg-primary hover:bg-primary-dark transition text-white font-semibold rounded-lg px-5 py-2.5 text-sm">
            + تسجيل استفادة جديدة
        </a>
    </div>

    <div class="space-y-3">
        @forelse($distributions as $dist)
            <a href="{{ route('admin.benefits.show', $dist) }}" class="block bg-white border border-line rounded-2xl p-5 hover:border-primary/40 transition">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        @if($dist->organization->logo_url)
                            <img src="{{ $dist->organization->logo_url }}" class="w-10 h-10 rounded-lg object-cover border border-line">
                        @else
                            <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary font-display font-bold">
                                {{ mb_substr($dist->organization->name, 0, 1) }}
                            </div>
                        @endif
                        <div>
                            <p class="font-semibold">{{ $dist->type }}</p>
                            <p class="text-xs text-muted">{{ $dist->organization->name }}</p>
                        </div>
                    </div>
                    <div class="text-left">
                        <p class="text-sm font-semibold text-primary">{{ $dist->families_count }} مستفيد</p>
                        <p class="text-xs text-muted">{{ $dist->distributed_at->format('Y-m-d') }}</p>
                    </div>
                </div>
            </a>
        @empty
            <p class="text-center text-muted bg-white border border-line rounded-2xl py-10">لا توجد استفادات مسجَّلة بعد.</p>
        @endforelse
    </div>

    <div class="mt-5">{{ $distributions->links() }}</div>
@endsection
