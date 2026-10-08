@extends('layouts.app')
@section('title', 'نتيجة الاستيراد')

@section('content')
    <div class="mb-6">
        <h1 class="text-xl font-bold mb-1">انتهى الاستيراد</h1>
        <p class="text-sm text-muted">نزّل ملف بيانات الدخول واحفظه الآن. بعد حذفه من السيرفر ما في طريقة لعرض كلمات المرور مرة ثانية.</p>
    </div>

    <div class="grid grid-cols-3 gap-3 mb-6">
        <div class="bg-white border border-line rounded-2xl p-4">
            <p class="text-xs text-muted mb-1">تم إنشاؤهم</p>
            <p class="text-2xl font-bold text-approved">{{ count($created) }}</p>
        </div>
        <div class="bg-white border border-line rounded-2xl p-4">
            <p class="text-xs text-muted mb-1">فشل</p>
            <p class="text-2xl font-bold text-rejected">{{ count($failed) }}</p>
        </div>
        <div class="bg-white border border-line rounded-2xl p-4">
            <p class="text-xs text-muted mb-1">تم تخطيهم</p>
            <p class="text-2xl font-bold text-pending">{{ count($skipped) }}</p>
        </div>
    </div>

    <div class="flex flex-wrap gap-3 mb-6">
        <a href="{{ route('admin.import.download', $token) }}"
           class="bg-primary hover:bg-primary-dark transition text-white font-semibold rounded-lg px-6 py-2.5 text-sm">
            تنزيل بيانات الدخول (Excel)
        </a>
        <button type="button" id="copy-btn" class="border border-line bg-white rounded-lg px-6 py-2.5 text-sm font-semibold">نسخ الكل</button>
        <form method="POST" action="{{ route('admin.import.destroy', $token) }}"
              onsubmit="return confirm('بعد الحذف ما بتقدر تنزّل بيانات الدخول مرة ثانية. متأكد إنك حفظتها؟');">
            @csrf
            @method('DELETE')
            <button class="border border-rejected/40 text-rejected bg-white rounded-lg px-6 py-2.5 text-sm font-semibold">حذف بيانات الدخول من السيرفر</button>
        </form>
    </div>

    <p class="text-xs text-muted mb-6">الملف يُحذف تلقائياً من السيرفر بعد 3 أيام.</p>

    @if(count($failed) > 0)
        <div class="bg-white border border-rejected/30 rounded-2xl overflow-hidden mb-6">
            <div class="px-5 py-3 border-b border-line text-sm font-bold text-rejected">صفوف فشلت</div>
            <table class="w-full text-sm">
                <tbody>
                    @foreach($failed as $f)
                        <tr class="border-t border-line first:border-0">
                            <td class="px-4 py-2.5 text-muted w-16">{{ $f['row'] }}</td>
                            <td class="px-4 py-2.5">{{ $f['name'] }}</td>
                            <td class="px-4 py-2.5 text-rejected">{{ $f['reason'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="bg-white border border-line rounded-2xl overflow-hidden mb-6">
        <div class="px-5 py-3 border-b border-line text-sm font-bold">بيانات الدخول</div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[560px]" id="cred-table">
                <thead class="bg-canvas">
                    <tr class="text-right text-xs text-muted">
                        <th class="px-4 py-2.5 font-semibold">الاسم</th>
                        <th class="px-4 py-2.5 font-semibold">اسم المستخدم</th>
                        <th class="px-4 py-2.5 font-semibold">كلمة المرور</th>
                        <th class="px-4 py-2.5 font-semibold">النوع</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($created as $c)
                        <tr class="border-t border-line">
                            <td class="px-4 py-2.5 font-medium">{{ $c['name'] }}</td>
                            <td class="px-4 py-2.5 font-mono" dir="ltr">{{ $c['username'] }}</td>
                            <td class="px-4 py-2.5 font-mono" dir="ltr">{{ $c['password'] }}</td>
                            <td class="px-4 py-2.5 text-xs text-muted">{{ $c['type'] === 'family' ? 'حساب + أسرة' : 'حساب فقط' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if(count($skipped) > 0)
        <details class="bg-white border border-line rounded-2xl overflow-hidden">
            <summary class="px-5 py-3 text-sm font-bold cursor-pointer">الصفوف التي تم تخطيها ({{ count($skipped) }})</summary>
            <table class="w-full text-sm">
                <tbody>
                    @foreach($skipped as $s)
                        <tr class="border-t border-line">
                            <td class="px-4 py-2.5 text-muted w-16">{{ $s['row'] }}</td>
                            <td class="px-4 py-2.5">{{ $s['name'] !== '' ? $s['name'] : '—' }}</td>
                            <td class="px-4 py-2.5 text-pending">{{ $s['reason'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </details>
    @endif

    <script>
        document.getElementById('copy-btn').addEventListener('click', function () {
            var lines = [];
            document.querySelectorAll('#cred-table tbody tr').forEach(function (tr) {
                var td = tr.querySelectorAll('td');
                lines.push([td[0].innerText.trim(), td[1].innerText.trim(), td[2].innerText.trim()].join('\t'));
            });
            var text = lines.join('\n');
            var btn = this;
            function done() { btn.textContent = 'تم النسخ'; setTimeout(function () { btn.textContent = 'نسخ الكل'; }, 1800); }
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(done);
            } else {
                var ta = document.createElement('textarea');
                ta.value = text; document.body.appendChild(ta); ta.select();
                document.execCommand('copy'); document.body.removeChild(ta); done();
            }
        });
    </script>
@endsection
