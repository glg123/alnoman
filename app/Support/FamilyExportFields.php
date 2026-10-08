<?php

namespace App\Support;

use App\Models\Family;
use Illuminate\Support\Collection;

class FamilyExportFields
{
    /**
     * كل حقل متاح للتصدير: مفتاح فريد => [label, group].
     * group يحدد مصدر البيانات: family | child | special_case
     */
    public static function catalog(): array
    {
        return [
            // بيانات الأسرة
            'family.full_name'        => ['label' => 'الاسم الرباعي (رب الأسرة)', 'group' => 'family'],
            'family.national_id'      => ['label' => 'رقم هوية رب الأسرة',        'group' => 'family'],
            'family.wife_name'        => ['label' => 'اسم الزوجة',                 'group' => 'family'],
            'family.wife_national_id' => ['label' => 'رقم هوية الزوجة',            'group' => 'family'],
            'family.members_count'    => ['label' => 'عدد أفراد الأسرة',           'group' => 'family'],
            'family.status'           => ['label' => 'حالة الطلب',                 'group' => 'family'],
            'family.created_at'       => ['label' => 'تاريخ التسجيل',              'group' => 'family'],

            // بيانات الأطفال
            'child.full_name'   => ['label' => 'اسم الطفل',      'group' => 'child'],
            'child.national_id' => ['label' => 'رقم هوية الطفل', 'group' => 'child'],
            'child.age'         => ['label' => 'عمر الطفل',      'group' => 'child'],

            // الحالات الخاصة
            'special_case.type'        => ['label' => 'نوع الحالة الخاصة',     'group' => 'special_case'],
            'special_case.description' => ['label' => 'تفاصيل الحالة',         'group' => 'special_case'],
            'special_case.child_name'  => ['label' => 'الطفل المرتبط بالحالة', 'group' => 'special_case'],
        ];
    }

    /** الحقول مجمّعة لبناء واجهة الاختيار (checkboxes) */
    public static function groupedForUi(): array
    {
        $groupLabels = [
            'family'       => 'بيانات الأسرة',
            'child'        => 'بيانات الأطفال',
            'special_case' => 'الحالات الخاصة',
        ];

        $grouped = [];
        foreach (self::catalog() as $key => $field) {
            $grouped[$field['group']]['label'] ??= $groupLabels[$field['group']];
            $grouped[$field['group']]['fields'][] = ['key' => $key, 'label' => $field['label']];
        }

        return $grouped;
    }

    /**
     * يحدد أدق مستوى تفصيل مطلوب بناءً على الحقول المختارة.
     * special_case أدق من child، وchild أدق من family.
     * هذا ما يجعل التصدير "ديناميكي": لو اخترت حقل طفل، الصفوف تصير لكل طفل
     * (مع تكرار حقول الأسرة المختارة بجانبه)، وهكذا.
     */
    public static function resolveLevel(array $selectedKeys): string
    {
        $catalog = self::catalog();
        $groups = array_map(fn ($key) => $catalog[$key]['group'] ?? null, $selectedKeys);

        if (in_array('special_case', $groups, true)) {
            return 'special_case';
        }
        if (in_array('child', $groups, true)) {
            return 'child';
        }

        return 'family';
    }

    /** عناوين الأعمدة بنفس ترتيب اختيار المستخدم */
    public static function headings(array $selectedKeys): array
    {
        $catalog = self::catalog();

        return array_values(array_map(
            fn ($key) => $catalog[$key]['label'] ?? $key,
            $selectedKeys
        ));
    }

    /**
     * يبني صفوف التصدير حسب أدق مستوى مطلوب.
     * كل صف = مصفوفة قيم بنفس ترتيب $selectedKeys.
     */
    public static function buildRows(Collection $families, array $selectedKeys): array
    {
        $level = self::resolveLevel($selectedKeys);
        $rows = [];

        foreach ($families as $family) {
            if ($level === 'family') {
                $rows[] = self::rowFrom($selectedKeys, $family);
                continue;
            }

            if ($level === 'child') {
                if ($family->children->isEmpty()) {
                    $rows[] = self::rowFrom($selectedKeys, $family);
                    continue;
                }
                foreach ($family->children as $child) {
                    $rows[] = self::rowFrom($selectedKeys, $family, $child);
                }
                continue;
            }

            // level === special_case
            if ($family->specialCases->isEmpty()) {
                $rows[] = self::rowFrom($selectedKeys, $family);
                continue;
            }
            foreach ($family->specialCases as $case) {
                $relatedChild = $case->child_id
                    ? $family->children->firstWhere('id', $case->child_id)
                    : null;
                $rows[] = self::rowFrom($selectedKeys, $family, $relatedChild, $case);
            }
        }

        return $rows;
    }

    private static function rowFrom(array $selectedKeys, Family $family, $child = null, $case = null): array
    {
        $row = [];

        foreach ($selectedKeys as $key) {
            $row[] = match ($key) {
                'family.full_name'         => $family->full_name,
                'family.national_id'       => $family->national_id,
                'family.wife_name'         => $family->wife_name,
                'family.wife_national_id'  => $family->wife_national_id,
                'family.members_count'     => $family->members_count,
                'family.status'            => self::statusLabel($family->status),
                'family.created_at'        => optional($family->created_at)->format('Y-m-d'),
                'child.full_name'          => $child->full_name ?? '',
                'child.national_id'        => $child->national_id ?? '',
                'child.age'                => $child->age ?? '',
                'special_case.type'        => $case->type ?? '',
                'special_case.description' => $case->description ?? '',
                'special_case.child_name'  => $child->full_name ?? (($case && !$case->child_id) ? 'رب الأسرة' : ''),
                default                    => '',
            };
        }

        return $row;
    }

    private static function statusLabel(string $status): string
    {
        return match ($status) {
            'pending'  => 'بانتظار المراجعة',
            'approved' => 'معتمدة',
            'rejected' => 'مرفوضة',
            default    => $status,
        };
    }
}
