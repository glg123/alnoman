<?php

namespace App\Http\Controllers\Admin;

use App\Exports\DynamicFamiliesExport;
use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Support\FamilyExportFields;
use App\Support\PdfArabicFont;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class FamilyExportController extends Controller
{
    public function export(Request $request)
    {
        $data = $request->validate([
            'fields'       => ['required', 'array', 'min:1'],
            'fields.*'     => ['string', 'in:' . implode(',', array_keys(FamilyExportFields::catalog()))],
            'format'       => ['required', 'in:xlsx,pdf'],
            'status'       => ['nullable', 'in:pending,approved,rejected,all'],
            'family_ids'   => ['nullable', 'array'],
            'family_ids.*' => ['integer', 'exists:families,id'],
        ], [
            'fields.required' => 'اختر حقلاً واحداً على الأقل قبل التصدير.',
        ]);

        $status = $data['status'] ?? 'all';
        $familyIds = array_filter($data['family_ids'] ?? []);

        $families = Family::with(['children', 'specialCases'])
            // لو محدد أسر بعينها من الجدول، هاي لها الأولوية وبتتجاهل فلتر الحالة.
            ->when(
                !empty($familyIds),
                fn ($q) => $q->whereIn('id', $familyIds),
                fn ($q) => $q->when($status !== 'all', fn ($q2) => $q2->where('status', $status))
            )
            ->latest()
            ->get();

        $headings = FamilyExportFields::headings($data['fields']);
        $rows     = FamilyExportFields::buildRows($families, $data['fields']);

        $filename = 'بيانات-الأسر-' . now()->format('Y-m-d-His');

        if ($data['format'] === 'xlsx') {
            return Excel::download(new DynamicFamiliesExport($rows, $headings), "{$filename}.xlsx");
        }

        $pdf = Pdf::loadView('admin.families.export-pdf', [
            'headings' => $headings,
            'rows'     => $rows,
        ])->setPaper('a4', 'landscape');

        // يسجّل خط Amiri العربي حتى تنعرض الحروف متصلة وصحيحة داخل الـ PDF.
        PdfArabicFont::register($pdf->getDomPDF());

        return $pdf->download("{$filename}.pdf");
    }
}
