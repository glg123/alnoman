@extends('layouts.app')
@section('title', 'المؤسسات والجمعيات')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="font-display text-xl font-bold">المؤسسات والجمعيات</h1>
        <a href="{{ route('admin.organizations.create') }}"
           class="bg-primary hover:bg-primary-dark transition text-white font-semibold rounded-lg px-5 py-2.5 text-sm">
            + إضافة مؤسسة
        </a>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($organizations as $org)
            <div class="bg-white border border-line rounded-2xl p-5">
                <div class="flex items-center gap-3 mb-3">
                    @if($org->logo_url)
                        <img src="{{ $org->logo_url }}" alt="{{ $org->name }}" class="w-12 h-12 rounded-xl object-cover border border-line">
                    @else
                        <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary font-display font-bold text-lg">
                            {{ mb_substr($org->name, 0, 1) }}
                        </div>
                    @endif
                    <div class="min-w-0">
                        <p class="font-semibold truncate">{{ $org->name }}</p>
                        <p class="text-xs text-muted">{{ $org->distributions_count }} توزيع مسجَّل</p>
                    </div>
                </div>
                @if($org->description)
                    <p class="text-sm text-muted mb-3">{{ \Illuminate\Support\Str::limit($org->description, 90) }}</p>
                @endif
                @if($org->phone)
                    <p class="text-xs text-muted mb-1">الهاتف: {{ $org->phone }}</p>
                @endif
                @if($org->address)
                    <p class="text-xs text-muted mb-3">العنوان: {{ $org->address }}</p>
                @endif
                <a href="{{ route('admin.organizations.edit', $org) }}" class="text-sm font-semibold text-primary hover:text-primary-dark">تعديل البيانات</a>
            </div>
        @empty
            <p class="col-span-full text-center text-muted bg-white border border-line rounded-2xl py-10">
                لا توجد مؤسسات مضافة بعد.
                <a href="{{ route('admin.organizations.create') }}" class="text-primary font-semibold hover:text-primary-dark underline">أضف أول مؤسسة</a>
            </p>
        @endforelse
    </div>

    <div class="mt-5">{{ $organizations->links() }}</div>
@endsection
