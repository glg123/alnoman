@php
    $existingChildren = isset($family) ? $family->children->values() : collect();
    $existingCases = isset($family) ? $family->specialCases->values() : collect();

    $caseChildIndex = function ($sc) use ($existingChildren) {
        if (! $sc->child_id) return null;
        $idx = $existingChildren->search(fn($c) => $c->id === $sc->child_id);
        return $idx === false ? null : $idx;
    };

    $steps = [
        ['num' => 1, 'label' => 'بيانات الأسرة'],
        ['num' => 2, 'label' => 'الأطفال'],
        ['num' => 3, 'label' => 'حالات خاصة'],
        ['num' => 4, 'label' => 'مراجعة وإرسال'],
    ];
@endphp

{{-- مؤشر الخطوات --}}
<div class="flex items-center mb-8 overflow-x-auto pb-1" id="stepper-nav">
    @foreach($steps as $i => $s)
        <div class="flex items-center {{ !$loop->last ? 'flex-1' : '' }}">
            <div class="flex flex-col items-center shrink-0">
                <div data-step-circle="{{ $s['num'] }}"
                     class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold border-2 transition-colors {{ $i === 0 ? 'bg-primary border-primary text-white' : 'bg-white border-line text-muted' }}">
                    {{ $s['num'] }}
                </div>
                <span data-step-label="{{ $s['num'] }}" class="text-xs mt-1.5 font-semibold whitespace-nowrap {{ $i === 0 ? 'text-primary' : 'text-muted' }}">{{ $s['label'] }}</span>
            </div>
            @if(!$loop->last)
                <div data-step-line="{{ $s['num'] }}" class="h-0.5 flex-1 bg-line mx-2 mb-5"></div>
            @endif
        </div>
    @endforeach
</div>

<form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="space-y-8" id="family-wizard-form">
    @csrf
    @if($formMethod === 'PUT') @method('PUT') @endif

    {{-- ===== خطوة 1: بيانات الأسرة ===== --}}
    <div data-step-panel="1">
        <section class="bg-white border border-line rounded-2xl p-5 sm:p-6 mb-4">
            <h2 class="font-display font-bold text-lg mb-1">بيانات رب الأسرة</h2>
            <p class="text-sm text-muted mb-5">الاسم الرباعي كما هو مسجل بالهوية.</p>

            <div class="grid sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-semibold mb-1.5">الاسم الرباعي</label>
                    <input type="text" name="full_name" value="{{ old('full_name', $family->full_name ?? '') }}" required
                           class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    @error('full_name') <p class="text-rejected text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1.5">رقم الهوية</label>
                    <input type="text" name="national_id" value="{{ old('national_id', $family->national_id ?? '') }}" required
                           class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    @error('national_id') <p class="text-rejected text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1.5">عدد أفراد الأسرة</label>
                    <input type="number" min="1" name="members_count" value="{{ old('members_count', $family->members_count ?? '') }}" required
                           class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    @error('members_count') <p class="text-rejected text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-semibold mb-1.5">صورة الهوية (اختياري)</label>
                    @if(isset($family) && $family->national_id_photo_url)
                        <div class="mb-2">
                            <a href="{{ $family->national_id_photo_url }}" target="_blank" class="text-xs font-semibold text-primary hover:text-primary-dark underline">عرض الصورة الحالية</a>
                            <span class="text-xs text-muted">— اختر صورة جديدة فقط إن أردت استبدالها</span>
                        </div>
                    @endif
                    <input type="hidden" name="existing_national_id_photo" value="{{ $family->national_id_photo_path ?? '' }}">
                    @include('family._upload-field', ['name' => 'national_id_photo', 'accept' => 'image/*', 'hint' => 'JPG, PNG — حتى 4 ميجابايت'])
                    @error('national_id_photo') <p class="text-rejected text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="bg-white border border-line rounded-2xl p-5 sm:p-6">
            <h2 class="font-display font-bold text-lg mb-1">بيانات الزوجة</h2>
            <p class="text-sm text-muted mb-5">اتركها فارغة إن لم تنطبق.</p>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold mb-1.5">اسم الزوجة</label>
                    <input type="text" name="wife_name" value="{{ old('wife_name', $family->wife_name ?? '') }}"
                           class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1.5">رقم هوية الزوجة</label>
                    <input type="text" name="wife_national_id" value="{{ old('wife_national_id', $family->wife_national_id ?? '') }}"
                           class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
            </div>
        </section>
    </div>

    {{-- ===== خطوة 2: الأطفال ===== --}}
    <div data-step-panel="2" class="hidden">
        <section class="bg-white border border-line rounded-2xl p-5 sm:p-6">
            <div class="flex items-center justify-between mb-1">
                <h2 class="font-display font-bold text-lg">الأطفال</h2>
                <button type="button" onclick="addChildRow()"
                        class="text-sm font-semibold text-primary hover:text-primary-dark">+ إضافة طفل</button>
            </div>
            <p class="text-sm text-muted mb-5">أضف كل طفل على حدة — اختياري إن لم يوجد أطفال.</p>

            <div id="children-list" class="space-y-4">
                @forelse($existingChildren as $index => $child)
                    <div class="border border-line rounded-xl p-4 relative" data-child-row data-child-index="{{ $index }}">
                        <button type="button" onclick="removeChildRow(this)"
                                class="absolute top-3 left-3 text-muted hover:text-rejected text-xs font-semibold">حذف</button>
                        <div class="grid sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-semibold mb-1.5 text-muted">اسم الطفل</label>
                                <input type="text" name="children[{{ $index }}][full_name]" value="{{ $child->full_name }}" required
                                       data-child-name-input oninput="refreshAllCaseSelects()"
                                       class="w-full rounded-lg border border-line px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold mb-1.5 text-muted">رقم الهوية (إن وجد)</label>
                                <input type="text" name="children[{{ $index }}][national_id]" value="{{ $child->national_id }}"
                                       class="w-full rounded-lg border border-line px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold mb-1.5 text-muted">العمر</label>
                                <input type="number" min="0" max="17" name="children[{{ $index }}][age]" value="{{ $child->age }}" required
                                       class="w-full rounded-lg border border-line px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                    </div>
                @empty
                    <p id="children-empty" class="text-sm text-muted border border-dashed border-line rounded-lg py-8 text-center">لا يوجد أطفال مضافون بعد.</p>
                @endforelse
            </div>
        </section>
    </div>

    {{-- ===== خطوة 3: حالات خاصة ===== --}}
    <div data-step-panel="3" class="hidden">
        <section class="bg-white border border-line rounded-2xl p-5 sm:p-6">
            <div class="flex items-center justify-between mb-1">
                <h2 class="font-display font-bold text-lg">حالات خاصة أو إعاقات</h2>
                <button type="button" onclick="addCaseRow()"
                        class="text-sm font-semibold text-primary hover:text-primary-dark">+ إضافة حالة</button>
            </div>
            <p class="text-sm text-muted mb-5">اختياري — أضفها إن وُجدت لدى رب الأسرة أو أحد الأطفال، مع إمكانية إرفاق سجل طبي أو ما يثبتها.</p>

            <div id="cases-list" class="space-y-4">
                @forelse($existingCases as $index => $sc)
                    @php $childIdx = $caseChildIndex($sc); @endphp
                    <div class="border border-line rounded-xl p-4 relative" data-case-row>
                        <button type="button" onclick="removeCaseRow(this)"
                                class="absolute top-3 left-3 text-muted hover:text-rejected text-xs font-semibold">حذف</button>
                        <div class="grid sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-semibold mb-1.5 text-muted">نوع الحالة</label>
                                <input type="text" name="special_cases[{{ $index }}][type]" value="{{ $sc->type }}" required
                                       placeholder="مثال: إعاقة حركية"
                                       class="w-full rounded-lg border border-line px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold mb-1.5 text-muted">تخص طفل؟ (اختياري)</label>
                                <select name="special_cases[{{ $index }}][child_index]" data-case-select
                                        class="w-full rounded-lg border border-line px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                    <option value="">لا — تخص رب الأسرة</option>
                                    @foreach($existingChildren as $cIndex => $c)
                                        <option value="{{ $cIndex }}" {{ (string) $childIdx === (string) $cIndex ? 'selected' : '' }}>{{ $c->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold mb-1.5 text-muted">تفاصيل إضافية</label>
                                <input type="text" name="special_cases[{{ $index }}][description]" value="{{ $sc->description }}"
                                       class="w-full rounded-lg border border-line px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div class="sm:col-span-3">
                                <label class="block text-xs font-semibold mb-1.5 text-muted">سجل طبي / ما يثبت الحالة (اختياري)</label>
                                @if($sc->document_url)
                                    <div class="mb-2">
                                        <a href="{{ $sc->document_url }}" target="_blank" class="text-xs font-semibold text-primary hover:text-primary-dark underline">عرض المرفق الحالي</a>
                                        <span class="text-xs text-muted">— اختر ملف جديد فقط إن أردت استبداله</span>
                                    </div>
                                @endif
                                <input type="hidden" name="special_cases[{{ $index }}][existing_document]" value="{{ $sc->document_path }}">
                                @include('family._upload-field', ['name' => "special_cases[{$index}][document]", 'accept' => '.pdf,.jpg,.jpeg,.png', 'hint' => 'صورة أو PDF — حتى 5 ميجابايت'])
                            </div>
                        </div>
                    </div>
                @empty
                    <p id="cases-empty" class="text-sm text-muted border border-dashed border-line rounded-lg py-8 text-center">لا توجد حالات مضافة.</p>
                @endforelse
            </div>
        </section>
    </div>

    {{-- ===== خطوة 4: مراجعة وإرسال ===== --}}
    <div data-step-panel="4" class="hidden">
        <section class="bg-white border border-line rounded-2xl p-6 sm:p-8 text-center">
            <div class="w-14 h-14 rounded-full bg-primary/10 flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h2 class="font-display font-bold text-lg mb-2">جاهز للإرسال</h2>
            <p class="text-sm text-muted max-w-sm mx-auto mb-1">راجع الخطوات السابقة للتأكد من صحة البيانات قبل الإرسال.</p>
            <p class="text-sm text-muted max-w-sm mx-auto">بعد الإرسال، ستُراجَع بياناتك من إدارة المخيم قبل اعتمادها نهائياً.</p>
        </section>
    </div>

    {{-- أزرار التنقل --}}
    <div class="flex items-center justify-between gap-3 sticky bottom-0 bg-canvas py-3 -mx-4 px-4 sm:mx-0 sm:px-0 sm:static sm:bg-transparent">
        <button type="button" id="btn-back" onclick="wizardBack()"
                class="hidden text-sm font-semibold text-muted hover:text-ink px-5 py-2.5">
            ← السابق
        </button>
        <a href="{{ route('family.show') }}" id="btn-cancel" class="text-sm font-semibold text-muted hover:text-ink px-5 py-2.5">إلغاء</a>

        <div class="flex-1"></div>

        <button type="button" id="btn-next" onclick="wizardNext()"
                class="bg-primary hover:bg-primary-dark transition text-white font-semibold rounded-lg px-6 py-2.5 text-sm">
            التالي ←
        </button>
        <button type="submit" id="btn-submit" class="hidden bg-primary hover:bg-primary-dark transition text-white font-semibold rounded-lg px-6 py-2.5 text-sm">
            {{ $submitLabel }}
        </button>
    </div>
</form>

<script>
    var childIndexCounter = {{ $existingChildren->count() }};
    var caseIndexCounter = {{ $existingCases->count() }};
    var totalSteps = 4;
    var currentStep = 1;

    /* ---------- التنقل بين الخطوات ---------- */
    function showStep(n) {
        document.querySelectorAll('[data-step-panel]').forEach(function (el) {
            el.classList.toggle('hidden', el.getAttribute('data-step-panel') != n);
        });
        document.querySelectorAll('[data-step-circle]').forEach(function (el) {
            var s = parseInt(el.getAttribute('data-step-circle'), 10);
            el.classList.remove('bg-primary', 'border-primary', 'text-white', 'bg-approved', 'border-approved');
            if (s < n) { el.classList.add('bg-approved', 'border-approved', 'text-white'); el.innerHTML = '✓'; }
            else if (s === n) { el.classList.add('bg-primary', 'border-primary', 'text-white'); el.textContent = s; }
            else { el.classList.add('bg-white', 'border-line', 'text-muted'); el.textContent = s; }
        });
        document.querySelectorAll('[data-step-label]').forEach(function (el) {
            var s = parseInt(el.getAttribute('data-step-label'), 10);
            el.classList.toggle('text-primary', s <= n);
            el.classList.toggle('text-muted', s > n);
        });
        document.querySelectorAll('[data-step-line]').forEach(function (el) {
            var s = parseInt(el.getAttribute('data-step-line'), 10);
            el.classList.toggle('bg-approved', s < n);
            el.classList.toggle('bg-line', s >= n);
        });

        document.getElementById('btn-back').classList.toggle('hidden', n === 1);
        document.getElementById('btn-cancel').classList.toggle('hidden', n !== 1);
        document.getElementById('btn-next').classList.toggle('hidden', n === totalSteps);
        document.getElementById('btn-submit').classList.toggle('hidden', n !== totalSteps);

        currentStep = n;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function validateStep(n) {
        var panel = document.querySelector('[data-step-panel="' + n + '"]');
        var invalid = panel.querySelector(':invalid');
        if (invalid) { invalid.reportValidity(); return false; }
        return true;
    }

    function wizardNext() {
        if (!validateStep(currentStep)) return;
        showStep(Math.min(currentStep + 1, totalSteps));
    }

    function wizardBack() {
        showStep(Math.max(currentStep - 1, 1));
    }

    // قبل الإرسال النهائي: تحقق من كل الخطوات، وارجع لأول خطوة فيها خطأ
    document.getElementById('family-wizard-form').addEventListener('submit', function (e) {
        for (var i = 1; i <= totalSteps; i++) {
            if (!validateStep(i)) {
                e.preventDefault();
                showStep(i);
                return;
            }
        }
    });

    /* ---------- معاينة اسم الملف المرفوع ---------- */
    function handleFilePreview(input) {
        var box = input.closest('[data-upload-box]');
        var label = box.querySelector('[data-upload-filename]');
        if (input.files && input.files.length > 0) {
            label.textContent = input.files[0].name;
            box.classList.add('border-primary', 'bg-primary/5');
        } else {
            label.textContent = '';
            box.classList.remove('border-primary', 'bg-primary/5');
        }
    }

    /* ---------- خيارات القوائم المنسدلة للحالات الخاصة ---------- */
    function childOptionsHtml(selectedValue) {
        var html = '<option value="">لا — تخص رب الأسرة</option>';
        document.querySelectorAll('#children-list [data-child-row]').forEach(function (row) {
            var idx = row.getAttribute('data-child-index');
            var nameInput = row.querySelector('[data-child-name-input]');
            var label = (nameInput && nameInput.value) ? nameInput.value : ('طفل ' + (parseInt(idx, 10) + 1));
            var selected = (String(selectedValue) === String(idx)) ? ' selected' : '';
            html += '<option value="' + idx + '"' + selected + '>' + label + '</option>';
        });
        return html;
    }

    function refreshAllCaseSelects() {
        document.querySelectorAll('#cases-list [data-case-select]').forEach(function (select) {
            var current = select.value;
            select.innerHTML = childOptionsHtml(current);
        });
    }

    function uploadBoxHtml(name, accept, title, hint) {
        return '<label data-upload-box class="flex items-center gap-3 border-2 border-dashed border-line rounded-lg px-4 py-3 cursor-pointer hover:border-primary/50 transition">' +
                '<svg class="w-5 h-5 text-muted shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M12 12v9m0-9l-3 3m3-3l3 3"/></svg>' +
                '<div class="min-w-0"><p class="text-sm font-semibold" data-upload-filename></p><p class="text-xs text-muted" data-upload-hint>' + hint + '</p></div>' +
                '<input type="file" name="' + name + '" accept="' + accept + '" class="hidden" onchange="handleFilePreview(this)">' +
            '</label>';
    }

    function addChildRow() {
        var container = document.getElementById('children-list');
        var emptyMsg = document.getElementById('children-empty');
        if (emptyMsg) emptyMsg.remove();

        var idx = childIndexCounter++;
        var div = document.createElement('div');
        div.className = 'border border-line rounded-xl p-4 relative';
        div.setAttribute('data-child-row', '');
        div.setAttribute('data-child-index', idx);
        div.innerHTML =
            '<button type="button" onclick="removeChildRow(this)" class="absolute top-3 left-3 text-muted hover:text-rejected text-xs font-semibold">حذف</button>' +
            '<div class="grid sm:grid-cols-3 gap-4">' +
                '<div><label class="block text-xs font-semibold mb-1.5 text-muted">اسم الطفل</label>' +
                '<input type="text" name="children[' + idx + '][full_name]" data-child-name-input required oninput="refreshAllCaseSelects()" class="w-full rounded-lg border border-line px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"></div>' +
                '<div><label class="block text-xs font-semibold mb-1.5 text-muted">رقم الهوية (إن وجد)</label>' +
                '<input type="text" name="children[' + idx + '][national_id]" class="w-full rounded-lg border border-line px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"></div>' +
                '<div><label class="block text-xs font-semibold mb-1.5 text-muted">العمر</label>' +
                '<input type="number" min="0" max="17" name="children[' + idx + '][age]" required class="w-full rounded-lg border border-line px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"></div>' +
            '</div>';
        container.appendChild(div);
        refreshAllCaseSelects();
    }

    function removeChildRow(btn) {
        btn.closest('[data-child-row]').remove();
        refreshAllCaseSelects();
        var container = document.getElementById('children-list');
        if (!container.querySelector('[data-child-row]')) {
            container.innerHTML = '<p id="children-empty" class="text-sm text-muted border border-dashed border-line rounded-lg py-8 text-center">لا يوجد أطفال مضافون بعد.</p>';
        }
    }

    function addCaseRow() {
        var container = document.getElementById('cases-list');
        var emptyMsg = document.getElementById('cases-empty');
        if (emptyMsg) emptyMsg.remove();

        var idx = caseIndexCounter++;
        var div = document.createElement('div');
        div.className = 'border border-line rounded-xl p-4 relative';
        div.setAttribute('data-case-row', '');
        div.innerHTML =
            '<button type="button" onclick="removeCaseRow(this)" class="absolute top-3 left-3 text-muted hover:text-rejected text-xs font-semibold">حذف</button>' +
            '<div class="grid sm:grid-cols-3 gap-4">' +
                '<div><label class="block text-xs font-semibold mb-1.5 text-muted">نوع الحالة</label>' +
                '<input type="text" name="special_cases[' + idx + '][type]" required placeholder="مثال: إعاقة حركية" class="w-full rounded-lg border border-line px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"></div>' +
                '<div><label class="block text-xs font-semibold mb-1.5 text-muted">تخص طفل؟ (اختياري)</label>' +
                '<select name="special_cases[' + idx + '][child_index]" data-case-select class="w-full rounded-lg border border-line px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"></select></div>' +
                '<div><label class="block text-xs font-semibold mb-1.5 text-muted">تفاصيل إضافية</label>' +
                '<input type="text" name="special_cases[' + idx + '][description]" class="w-full rounded-lg border border-line px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"></div>' +
                '<div class="sm:col-span-3"><label class="block text-xs font-semibold mb-1.5 text-muted">سجل طبي / ما يثبت الحالة (اختياري)</label>' +
                uploadBoxHtml('special_cases[' + idx + '][document]', '.pdf,.jpg,.jpeg,.png', '', 'صورة أو PDF — حتى 5 ميجابايت') +
                '</div>' +
            '</div>';
        container.appendChild(div);
        div.querySelector('[data-case-select]').innerHTML = childOptionsHtml('');
    }

    function removeCaseRow(btn) {
        btn.closest('[data-case-row]').remove();
        var container = document.getElementById('cases-list');
        if (!container.querySelector('[data-case-row]')) {
            container.innerHTML = '<p id="cases-empty" class="text-sm text-muted border border-dashed border-line rounded-lg py-8 text-center">لا توجد حالات مضافة.</p>';
        }
    }

    showStep(1);
</script>
