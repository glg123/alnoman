<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تصدير بيانات الأسر</title>
    <style>
        body { font-family: 'Amiri', 'DejaVu Sans', sans-serif; font-size: 11px; color: #1C1C1A; direction: rtl; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        p.meta { color: #6B6A63; margin: 0 0 16px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        thead th {
            background-color: #1F5C4F; color: #ffffff; text-align: right;
            padding: 7px 9px; font-size: 10.5px;
        }
        tbody td {
            border-bottom: 1px solid #E4E1D8; padding: 6px 9px;
        }
        tbody tr:nth-child(even) { background-color: #F6F5F1; }
    </style>
</head>
<body>
    <h1>تصدير بيانات الأسر — مخيم</h1>
    <p class="meta">تاريخ التصدير: {{ now()->format('Y-m-d H:i') }} — عدد الصفوف: {{ count($rows) }}</p>

    <table>
        <thead>
            <tr>
                @foreach($headings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($row as $value)
                        <td>{{ $value }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($headings) }}">لا توجد بيانات ضمن هذا التصنيف.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
