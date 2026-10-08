@extends('layouts.app')
@section('title', 'ربط الأعمدة')

@section('content')
    <div class="mb-6">
        <h1 class="text-xl font-bold mb-1">ربط أعمدة الملف</h1>
        <p class="text-sm text-muted">
            الملف: <span class="font-semibold text-ink">{{ $filename }}</span> — {{ $rowsCount }} صف.
            اخترت لك الأعمدة تلقائياً حسب عناوينها، عدّل ما يلزم وراقب المعاينة تحتها.
        </p>
    </div>

    @error('map')
        <div class="mb-5 rounded-lg border border-rejected/30 bg-rejected/10 text-rejected px-4 py-3 text-sm font-medium">{{ $message }}</div>
    @enderror

    <form method="POST" action="{{ route('admin.import.mapping.save', $token) }}" id="map-form" class="space-y-5">
        @csrf

        <div id="fields" class="space-y-4"></div>

        <div class="bg-white border border-line rounded-2xl p-5">
            <h2 class="font-bold text-sm mb-3">معاينة أول الصفوف بعد الربط</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[560px]">
                    <thead class="bg-canvas">
                        <tr class="text-right text-xs text-muted">
                            <th class="px-3 py-2 font-semibold">الاسم</th>
                            <th class="px-3 py-2 font-semibold">رقم الهوية</th>
                            <th class="px-3 py-2 font-semibold">الزوجة</th>
                            <th class="px-3 py-2 font-semibold">هوية الزوجة</th>
                            <th class="px-3 py-2 font-semibold">الأفراد</th>
                        </tr>
                    </thead>
                    <tbody id="preview-body"></tbody>
                </table>
            </div>
            <p id="unused" class="text-xs text-muted mt-3"></p>
        </div>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="bg-primary hover:bg-primary-dark transition text-white font-semibold rounded-lg px-6 py-2.5 text-sm">
                متابعة للمعاينة النهائية
            </button>
            <a href="{{ route('admin.import.create') }}" class="border border-line bg-white rounded-lg px-6 py-2.5 text-sm font-semibold">رفع ملف آخر</a>
        </div>
    </form>

    <script>
        (function () {
            var FIELDS = @json($fields);
            var HEADERS = @json($headers);
            var SAMPLE = @json($sample);
            var STATE = @json($mapping);
            var fieldsBox = document.getElementById('fields');

            function esc(s) {
                return String(s).replace(/[&<>"']/g, function (c) {
                    return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c];
                });
            }

            function optionsHtml(selected) {
                var html = '<option value="">— لا يوجد —</option>';
                HEADERS.forEach(function (h, i) {
                    var ex = SAMPLE[0] && SAMPLE[0][i] ? '  (مثال: ' + SAMPLE[0][i] + ')' : '';
                    html += '<option value="' + i + '"' + (String(selected) === String(i) ? ' selected' : '') + '>' +
                        (i + 1) + '. ' + esc(h + ex) + '</option>';
                });
                return html;
            }

            function render() {
                fieldsBox.innerHTML = '';
                Object.keys(FIELDS).forEach(function (key) {
                    var def = FIELDS[key];
                    STATE[key] = STATE[key] || [];
                    var list = STATE[key] && STATE[key].length ? STATE[key] : [''];

                    var box = document.createElement('div');
                    box.className = 'bg-white border border-line rounded-2xl p-5';

                    var title = '<div class="flex items-center gap-2 mb-1"><h2 class="font-bold text-sm">' + esc(def.label) + '</h2>' +
                        (def.required ? '<span class="text-xs text-rejected font-semibold">مطلوب</span>' : '<span class="text-xs text-muted">اختياري</span>') + '</div>';
                    var help = def.help ? '<p class="text-xs text-muted mb-3 leading-relaxed">' + esc(def.help) + '</p>' : '<div class="mb-3"></div>';

                    var rows = '';
                    list.forEach(function (sel, pos) {
                        rows += '<div class="flex items-center gap-2 mb-2" data-pos="' + pos + '">' +
                            (def.multi ? '<span class="text-xs text-muted w-14 shrink-0">' + (pos === 0 ? 'العمود 1' : '+ العمود ' + (pos + 1)) + '</span>' : '') +
                            '<select name="map[' + key + '][]" data-key="' + key + '" data-pos="' + pos + '" class="flex-1 min-w-0 rounded-lg border border-line px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-primary">' +
                            optionsHtml(sel) + '</select>' +
                            (def.multi && list.length > 1 ? '<button type="button" data-remove="' + key + ':' + pos + '" class="text-rejected text-lg px-2" aria-label="حذف">×</button>' : '') +
                            '</div>';
                    });

                    var add = def.multi && list.length < 6
                        ? '<button type="button" data-add="' + key + '" class="text-primary text-sm font-semibold mt-1">+ إضافة عمود (يُدمج بالترتيب)</button>'
                        : '';

                    box.innerHTML = title + help + rows + add;
                    fieldsBox.appendChild(box);
                });
                updatePreview();
            }

            fieldsBox.addEventListener('change', function (e) {
                var s = e.target;
                if (!s.dataset || !s.dataset.key) return;
                STATE[s.dataset.key][Number(s.dataset.pos)] = s.value;
                updatePreview();
            });

            fieldsBox.addEventListener('click', function (e) {
                var add = e.target.getAttribute('data-add');
                var rem = e.target.getAttribute('data-remove');
                if (add) {
                    STATE[add] = (STATE[add] && STATE[add].length ? STATE[add] : ['']).concat(['']);
                    render();
                } else if (rem) {
                    var p = rem.split(':');
                    STATE[p[0]].splice(Number(p[1]), 1);
                    render();
                }
            });

            function picked(key) {
                return (STATE[key] || []).filter(function (v) { return v !== '' && v !== null && v !== undefined; });
            }

            function cell(row, i) {
                return (row[Number(i)] || '').toString().trim();
            }

            function joinCols(row, key) {
                return picked(key).map(function (i) { return cell(row, i); }).filter(Boolean).join(' ');
            }

            function firstCol(row, key) {
                var p = picked(key);
                return p.length ? cell(row, p[0]) : '';
            }

            function updatePreview() {
                var body = document.getElementById('preview-body');
                var html = '';
                SAMPLE.forEach(function (row) {
                    var name = joinCols(row, 'full_name');
                    html += '<tr class="border-t border-line">' +
                        '<td class="px-3 py-2 font-medium' + (name ? '' : ' text-rejected') + '">' + (name ? esc(name) : 'فارغ') + '</td>' +
                        '<td class="px-3 py-2 text-muted">' + esc(firstCol(row, 'national_id') || '—') + '</td>' +
                        '<td class="px-3 py-2 text-muted">' + esc(joinCols(row, 'wife_name') || '—') + '</td>' +
                        '<td class="px-3 py-2 text-muted">' + esc(firstCol(row, 'wife_national_id') || '—') + '</td>' +
                        '<td class="px-3 py-2 text-muted">' + esc(firstCol(row, 'members_count') || '1') + '</td></tr>';
                });
                body.innerHTML = html;

                var used = {};
                Object.keys(FIELDS).forEach(function (k) { picked(k).forEach(function (i) { used[i] = true; }); });
                var unused = HEADERS.filter(function (h, i) { return !used[i]; });
                document.getElementById('unused').textContent = unused.length
                    ? 'أعمدة لن تُستخدم: ' + unused.join('، ')
                    : 'كل أعمدة الملف مستخدمة.';
            }

            render();
        })();
    </script>
@endsection
