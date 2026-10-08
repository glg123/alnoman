@extends('layouts.app')
@section('title', 'معاينة الاستيراد')

@section('content')
    <div class="mb-6">
        <h1 class="text-xl font-bold mb-1">معاينة قبل الاستيراد</h1>
        <p class="text-sm text-muted">ما تم حفظ شي لسا. راجع الأرقام ثم اضغط «بدء الاستيراد».</p>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        <div class="bg-white border border-line rounded-2xl p-4">
            <p class="text-xs text-muted mb-1">سيُستورد</p>
            <p class="text-2xl font-bold text-primary">{{ $total }}</p>
        </div>
        <div class="bg-white border border-line rounded-2xl p-4">
            <p class="text-xs text-muted mb-1">حساب + أسرة</p>
            <p class="text-2xl font-bold">{{ $families }}</p>
        </div>
        <div class="bg-white border border-line rounded-2xl p-4">
            <p class="text-xs text-muted mb-1">حساب فقط</p>
            <p class="text-2xl font-bold">{{ $usersOnly }}</p>
        </div>
        <div class="bg-white border border-line rounded-2xl p-4">
            <p class="text-xs text-muted mb-1">سيُتخطى</p>
            <p class="text-2xl font-bold text-pending">{{ $skippedCount }}</p>
        </div>
    </div>

    <div class="bg-white border border-line rounded-2xl p-5 mb-6" id="run-box">
        @if($total > 0)
            <div id="progress-wrap" class="hidden mb-4">
                <div class="flex justify-between text-xs text-muted mb-1.5">
                    <span id="progress-text">0 / {{ $total }}</span>
                    <span id="progress-pct">0%</span>
                </div>
                <div class="h-2.5 bg-canvas rounded-full overflow-hidden">
                    <div id="progress-bar" class="h-full bg-primary transition-all" style="width:0%"></div>
                </div>
                <p class="text-xs text-muted mt-2">لا تغلق الصفحة حتى ينتهي الاستيراد.</p>
            </div>
            <p id="run-error" class="hidden mb-3 text-sm text-rejected font-medium"></p>

            <div class="flex flex-wrap gap-3">
                <button type="button" id="start-btn"
                        class="bg-primary hover:bg-primary-dark transition text-white font-semibold rounded-lg px-6 py-2.5 text-sm">
                    {{ $cursor > 0 ? 'استكمال الاستيراد' : 'بدء الاستيراد' }}
                </button>
                <a id="back-link" href="{{ route('admin.import.mapping', $token) }}"
                   class="border border-line bg-white rounded-lg px-6 py-2.5 text-sm font-semibold">رجوع لتعديل الربط</a>
            </div>
        @else
            <p class="text-sm text-rejected font-medium mb-3">ما في أي صف قابل للاستيراد بهذا الربط.</p>
            <a href="{{ route('admin.import.mapping', $token) }}" class="border border-line bg-white rounded-lg px-6 py-2.5 text-sm font-semibold inline-block">رجوع لتعديل الربط</a>
        @endif
    </div>

    @if($total > 0)
        <div class="bg-white border border-line rounded-2xl overflow-hidden mb-6">
            <div class="px-5 py-3 border-b border-line text-sm font-bold">
                عيّنة مما سيُستورد
                @if($total > count($records)) <span class="text-xs text-muted font-normal">(أول {{ count($records) }} من {{ $total }})</span> @endif
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[640px]">
                    <thead class="bg-canvas">
                        <tr class="text-right text-xs text-muted">
                            <th class="px-4 py-2.5 font-semibold">صف</th>
                            <th class="px-4 py-2.5 font-semibold">الاسم</th>
                            <th class="px-4 py-2.5 font-semibold">رقم الهوية</th>
                            <th class="px-4 py-2.5 font-semibold">النوع</th>
                            <th class="px-4 py-2.5 font-semibold">ملاحظات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($records as $r)
                            <tr class="border-t border-line align-top">
                                <td class="px-4 py-2.5 text-muted">{{ $r['row'] }}</td>
                                <td class="px-4 py-2.5 font-medium">{{ $r['name'] }}</td>
                                <td class="px-4 py-2.5 text-muted">{{ $r['national_id'] ?? '—' }}</td>
                                <td class="px-4 py-2.5">{{ $r['type'] === 'family' ? 'حساب + أسرة' : 'حساب فقط' }}</td>
                                <td class="px-4 py-2.5 text-xs text-pending">{{ implode('، ', $r['notes']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($skippedCount > 0)
        <div class="bg-white border border-line rounded-2xl overflow-hidden">
            <div class="px-5 py-3 border-b border-line text-sm font-bold">
                صفوف سيتم تخطيها
                @if($skippedCount > count($skipped)) <span class="text-xs text-muted font-normal">(أول {{ count($skipped) }} من {{ $skippedCount }})</span> @endif
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[560px]">
                    <thead class="bg-canvas">
                        <tr class="text-right text-xs text-muted">
                            <th class="px-4 py-2.5 font-semibold">صف</th>
                            <th class="px-4 py-2.5 font-semibold">الاسم</th>
                            <th class="px-4 py-2.5 font-semibold">السبب</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($skipped as $s)
                            <tr class="border-t border-line">
                                <td class="px-4 py-2.5 text-muted">{{ $s['row'] }}</td>
                                <td class="px-4 py-2.5">{{ $s['name'] !== '' ? $s['name'] : '—' }}</td>
                                <td class="px-4 py-2.5 text-pending">{{ $s['reason'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($total > 0)
    <script>
        (function () {
            var RUN_URL = @json(route('admin.import.run', $token));
            var RESULT_URL = @json(route('admin.import.result', $token));
            var CSRF = @json(csrf_token());
            var TOTAL = @json($total);

            var btn = document.getElementById('start-btn');
            var wrap = document.getElementById('progress-wrap');
            var bar = document.getElementById('progress-bar');
            var txt = document.getElementById('progress-text');
            var pct = document.getElementById('progress-pct');
            var err = document.getElementById('run-error');
            var back = document.getElementById('back-link');
            var running = false;

            function show(cursor) {
                var p = Math.min(100, Math.round(cursor / TOTAL * 100));
                bar.style.width = p + '%';
                pct.textContent = p + '%';
                txt.textContent = cursor + ' / ' + TOTAL;
            }

            async function loop() {
                running = true;
                btn.disabled = true;
                btn.classList.add('opacity-60');
                back.classList.add('hidden');
                err.classList.add('hidden');
                wrap.classList.remove('hidden');

                try {
                    while (true) {
                        var res = await fetch(RUN_URL, {
                            method: 'POST',
                            headers: {'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json'}
                        });
                        var data = await res.json().catch(function () { return {}; });
                        if (!res.ok) {
                            throw new Error(data.error || data.message || ('خطأ ' + res.status));
                        }
                        show(data.cursor);
                        if (data.done) {
                            window.location.href = RESULT_URL;
                            return;
                        }
                    }
                } catch (e) {
                    err.textContent = 'توقف الاستيراد: ' + e.message + ' — اضغط «استكمال الاستيراد» ليكمل من حيث توقف.';
                    err.classList.remove('hidden');
                    btn.textContent = 'استكمال الاستيراد';
                    btn.disabled = false;
                    btn.classList.remove('opacity-60');
                    running = false;
                }
            }

            btn.addEventListener('click', function () { if (!running) loop(); });
            window.addEventListener('beforeunload', function (e) {
                if (running) { e.preventDefault(); e.returnValue = ''; }
            });
        })();
    </script>
    @endif
@endsection
