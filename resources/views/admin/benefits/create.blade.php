@extends('layouts.app')
@section('title', 'تسجيل استفادة جديدة')

@section('content')
    <h1 class="font-display text-xl font-bold mb-6">تسجيل استفادة جديدة</h1>

    @if($organizations->isEmpty())
        <div class="bg-white border border-line rounded-2xl p-8 text-center">
            <p class="text-sm text-muted mb-4">لا توجد مؤسسات مضافة بعد — أضف مؤسسة أولاً قبل تسجيل أي استفادة.</p>
            <a href="{{ route('admin.organizations.create') }}" class="text-primary font-semibold hover:text-primary-dark underline">+ إضافة مؤسسة</a>
        </div>
    @else
        <form method="POST" action="{{ route('admin.benefits.store') }}" class="space-y-6 pb-20">
            @csrf

            <section class="bg-white border border-line rounded-2xl p-5 sm:p-6">
                <h2 class="font-display font-bold text-base mb-4">1. تفاصيل الاستفادة</h2>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-1.5">المؤسسة</label>
                        <select name="organization_id" required
                                class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="">اختر المؤسسة</option>
                            @foreach($organizations as $org)
                                <option value="{{ $org->id }}" {{ old('organization_id') == $org->id ? 'selected' : '' }}>{{ $org->name }}</option>
                            @endforeach
                        </select>
                        @error('organization_id') <p class="text-rejected text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1.5">تاريخ التوزيع</label>
                        <input type="date" name="distributed_at" value="{{ old('distributed_at', now()->format('Y-m-d')) }}" required
                               class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                        @error('distributed_at') <p class="text-rejected text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-semibold mb-1.5">نوع الاستفادة</label>
                        <input type="text" id="type-input" name="type" value="{{ old('type') }}" required
                               placeholder="اختر من الأسفل أو اكتب نوعاً جديداً"
                               class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                        <div class="flex flex-wrap gap-2 mt-2">
                            @foreach(['كوبون غذائي', 'كوبون صحي', 'كوبون صحي وغذائي', 'مساعدة إيواء', 'مستلزمات تعليمية', 'مساعدة نقدية'] as $suggestion)
                                <button type="button" onclick="document.getElementById('type-input').value = '{{ $suggestion }}'"
                                        class="text-xs font-semibold px-3 py-1.5 rounded-full border border-line bg-white text-muted hover:border-primary hover:text-primary transition">
                                    {{ $suggestion }}
                                </button>
                            @endforeach
                        </div>
                        @error('type') <p class="text-rejected text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-semibold mb-1.5">تفاصيل إضافية (اختياري)</label>
                        <textarea name="description" rows="2" placeholder="مثال: سلة غذائية شهرية، أو كوبون بقيمة محددة..."
                                  class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">{{ old('description') }}</textarea>
                    </div>
                </div>
            </section>

            <section class="bg-white border border-line rounded-2xl p-5 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h2 class="font-display font-bold text-base">2. اختيار المستفيدين</h2>
                    <p class="text-xs text-muted">الأسر المعتمدة فقط: {{ $families->count() }}</p>
                </div>

                @if($families->isEmpty())
                    <p class="text-sm text-muted text-center py-6">لا توجد أسر معتمدة حالياً لاختيارها.</p>
                @else
                    <input type="text" id="family-search" placeholder="ابحث بالاسم أو رقم الهوية..."
                           oninput="filterFamilyList()"
                           class="w-full rounded-lg border border-line px-4 py-2.5 text-sm mb-3 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">

                    <div class="flex gap-4 mb-3">
                        <button type="button" onclick="toggleAllFamilies(true)" class="text-xs font-semibold text-primary hover:text-primary-dark">تحديد الكل الظاهر</button>
                        <button type="button" onclick="toggleAllFamilies(false)" class="text-xs font-semibold text-muted hover:text-ink">إلغاء التحديد</button>
                    </div>

                    <div class="border border-line rounded-xl max-h-96 overflow-y-auto divide-y divide-line">
                        @foreach($families as $family)
                            <label data-family-row data-search="{{ mb_strtolower($family->full_name.' '.$family->national_id) }}"
                                   class="flex items-center gap-3 px-4 py-3 hover:bg-canvas cursor-pointer">
                                <input type="checkbox" name="family_ids[]" value="{{ $family->id }}" onchange="updateSelectedCount()"
                                       {{ collect(old('family_ids', []))->contains($family->id) ? 'checked' : '' }}
                                       class="w-4 h-4 rounded border-line text-primary focus:ring-primary">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold truncate">{{ $family->full_name }}</p>
                                    <p class="text-xs text-muted">{{ $family->national_id }} — {{ $family->members_count }} أفراد</p>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    @error('family_ids') <p class="text-rejected text-xs mt-2">{{ $message }}</p> @enderror
                @endif
            </section>

            {{-- شريط سفلي ثابت: العدّاد + زر الحفظ --}}
            <div class="fixed bottom-0 inset-x-0 lg:right-72 bg-white border-t border-line px-4 py-3 z-30">
                <div class="max-w-5xl mx-auto flex items-center justify-between gap-3">
                    <p class="text-sm"><span id="selected-count" class="font-display font-bold text-primary text-lg">0</span> <span class="text-muted">مستفيد مختار</span></p>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('admin.benefits.index') }}" class="text-sm font-semibold text-muted hover:text-ink">إلغاء</a>
                        <button type="submit" class="bg-primary hover:bg-primary-dark transition text-white font-semibold rounded-lg px-6 py-2.5 text-sm">
                            تسجيل الاستفادة
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <script>
            function filterFamilyList() {
                var q = document.getElementById('family-search').value.trim().toLowerCase();
                document.querySelectorAll('[data-family-row]').forEach(function (row) {
                    row.classList.toggle('hidden', row.getAttribute('data-search').indexOf(q) === -1);
                });
            }
            function toggleAllFamilies(checked) {
                document.querySelectorAll('[data-family-row]:not(.hidden) input[type=checkbox]').forEach(function (cb) { cb.checked = checked; });
                updateSelectedCount();
            }
            function updateSelectedCount() {
                document.getElementById('selected-count').textContent = document.querySelectorAll('input[name="family_ids[]"]:checked').length;
            }
            updateSelectedCount();
        </script>
    @endif
@endsection
