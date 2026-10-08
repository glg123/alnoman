<?php

namespace App\Services\Import;

use App\Models\Family;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * منطق استيراد الأشخاص/الأسر من ملف Excel بعد ربط الأعمدة.
 *
 * كل صف = شخص (رب أسرة):
 *   - اسم + رقم هوية صالح  => حساب مستخدم + أسرة بحالة "قيد المراجعة"
 *   - اسم فقط              => حساب مستخدم فقط (يعبّي بياناته بنفسه)
 */
class PeopleImporter
{
    /** الحقول التي يمكن ربطها بأعمدة الملف. multi = يقبل دمج أكثر من عمود بالترتيب. */
    public const FIELDS = [
        'full_name' => [
            'label' => 'اسم رب الأسرة',
            'required' => true,
            'multi' => true,
            'help' => 'إذا كان الاسم مقسوماً على أعمدة (الأول، الأب، الجد، العائلة) اختر الأعمدة بالترتيب وسيُدمجون باسم واحد.',
        ],
        'national_id' => [
            'label' => 'رقم الهوية',
            'required' => false,
            'multi' => false,
            'help' => 'بدونه يُنشأ حساب فقط، ويُدخل المستخدم بيانات أسرته بنفسه.',
        ],
        'wife_name' => [
            'label' => 'اسم الزوجة',
            'required' => false,
            'multi' => true,
            'help' => null,
        ],
        'wife_national_id' => [
            'label' => 'رقم هوية الزوجة',
            'required' => false,
            'multi' => false,
            'help' => null,
        ],
        'members_count' => [
            'label' => 'عدد أفراد الأسرة',
            'required' => false,
            'multi' => false,
            'help' => 'إذا لم يوجد يُسجَّل 1.',
        ],
    ];

    public const MAX_MEMBERS = 60;

    // ------------------------------------------------------------ تطبيع النصوص

    public static function normalizeAr(string $s): string
    {
        $s = mb_strtolower($s);
        $s = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', $s) ?? $s;   // تشكيل + تطويل
        $s = strtr($s, ['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا', 'ى' => 'ي', 'ة' => 'ه', 'ؤ' => 'و', 'ئ' => 'ي']);
        $s = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $s) ?? $s;
        $s = preg_replace('/(?<![\p{L}])ال(?=\p{L})/u', '', $s) ?? $s;               // حذف "ال" التعريف
        $s = preg_replace('/\s+/u', ' ', $s) ?? $s;

        return trim($s);
    }

    public static function asciiDigits(string $s): string
    {
        return strtr($s, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
    }

    /** يرجع رقم الهوية نظيفاً، أو null إذا كان فارغاً/غير صالح. [الرقم, هل كان فيه قيمة أصلاً] */
    public static function cleanNationalId(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [null, false];
        }
        $v = self::asciiDigits($raw);
        $v = preg_replace('/[\s\-._\'\x{200E}\x{200F}]+/u', '', $v) ?? $v;

        return [preg_match('/^\d{5,20}$/', $v) ? $v : null, true];
    }

    public static function cleanName(string $v): string
    {
        $v = preg_replace('/\s+/u', ' ', $v) ?? $v;

        return trim($v);
    }

    // --------------------------------------------------------- تخمين الربط

    /**
     * يخمّن ربط الأعمدة من عناوينها. الناتج: حقل => [فهارس الأعمدة].
     */
    public static function guessMapping(array $headers): array
    {
        $map = array_fill_keys(array_keys(self::FIELDS), []);
        $parts = [];     // رتبة => [فهارس]
        $full = [];

        foreach ($headers as $i => $h) {
            // الأنماط أدناه مكتوبة بالصيغة المطبّعة (ة→ه، أ→ا، ئ→ي، بدون ال التعريف)
            $n = self::normalizeAr((string) $h);
            if ($n === '') {
                continue;
            }

            $isWife = (bool) preg_match('/زوج|حرم/u', $n);
            $isId = (bool) preg_match('/هويه|وطني|بطاقه|(^| )(id|identity|national|ssn)( |$)/u', $n);

            if ($isWife) {
                if ($isId) {
                    $map['wife_national_id'] ??= [];
                    if (! $map['wife_national_id']) {
                        $map['wife_national_id'][] = $i;
                    }
                } elseif (preg_match('/اسم|name/u', $n) || $n === 'زوجه' || $n === 'الزوجه') {
                    $map['wife_name'][] = $i;
                }
                continue;
            }

            if ($isId) {
                if (! $map['national_id']) {
                    $map['national_id'][] = $i;
                }
                continue;
            }

            if (preg_match('/(^| )(افراد|عدد|members|count)( |$)|عدد/u', $n)) {
                if (! $map['members_count']) {
                    $map['members_count'][] = $i;
                }
                continue;
            }

            if (preg_match('/(^| )ام( |$)|mother|جوال|هاتف|phone|mobile|عنوان|address|تاريخ|date|ملاحظ|note/u', $n)) {
                continue;
            }

            if (preg_match('/(^| )(اول|first|given)/u', $n)) {
                $parts[1][] = $i;
            } elseif (preg_match('/(^| )(اب|ابو|والد|father|middle|ثاني)( |$)/u', $n)) {
                $parts[2][] = $i;
            } elseif (preg_match('/(^| )(جد|grand)/u', $n)) {
                $parts[3][] = $i;
            } elseif (preg_match('/عايله|عيله|كنيه|لقب|family|last|surname|فخذ|حموله/u', $n)) {
                $parts[4][] = $i;
            } elseif (preg_match('/(^| )(اسم|name|رباعي|ثلاثي|كامل|رب)( |$)|^اسم/u', $n)) {
                $full[] = $i;
            }
        }

        if ($full) {
            $map['full_name'] = [$full[0]];
        } elseif ($parts) {
            ksort($parts);
            foreach ($parts as $idxs) {
                foreach ($idxs as $i) {
                    $map['full_name'][] = $i;
                }
            }
        }

        return $map;
    }

    // -------------------------------------------------------- بناء السجلات

    /**
     * يحوّل صفوف الملف إلى سجلات جاهزة للاستيراد حسب الربط.
     *
     * @param array $rows               [['row'=>n,'cells'=>[...]], ...] (بدون صف العناوين)
     * @param array $mapping            حقل => [فهارس الأعمدة]
     * @param array $existingFamilyIds  [رقم هوية => اسم] موجودة بالقاعدة
     * @param array $existingNameKeys   [اسم مطبّع => true] لأسماء حسابات موجودة
     * @return array{records: array, skipped: array}
     */
    public static function buildRecords(array $rows, array $mapping, array $existingFamilyIds = [], array $existingNameKeys = []): array
    {
        $records = [];
        $skipped = [];
        $seenIds = [];
        $seenNames = [];

        $join = function (array $cells, array $idxs): string {
            $parts = [];
            foreach ($idxs as $i) {
                $v = self::cleanName((string) ($cells[$i] ?? ''));
                if ($v !== '') {
                    $parts[] = $v;
                }
            }

            return implode(' ', $parts);
        };
        $first = fn (array $cells, array $idxs): string => isset($idxs[0]) ? trim((string) ($cells[$idxs[0]] ?? '')) : '';

        foreach ($rows as $row) {
            $cells = $row['cells'];
            $no = $row['row'];

            $name = $join($cells, $mapping['full_name'] ?? []);
            if ($name === '') {
                $skipped[] = ['row' => $no, 'name' => '', 'reason' => 'الاسم فارغ'];
                continue;
            }
            $nameKey = self::normalizeAr($name);

            $notes = [];

            [$nid, $hadNid] = self::cleanNationalId($first($cells, $mapping['national_id'] ?? []));
            if ($hadNid && $nid === null) {
                $notes[] = 'رقم الهوية غير صالح («' . $first($cells, $mapping['national_id']) . '») فلم تُنشأ أسرة';
            }

            $wifeName = $join($cells, $mapping['wife_name'] ?? []);
            [$wifeNid, $hadWifeNid] = self::cleanNationalId($first($cells, $mapping['wife_national_id'] ?? []));
            if ($hadWifeNid && $wifeNid === null) {
                $notes[] = 'رقم هوية الزوجة غير صالح فتُجوهل';
            }

            $members = 1;
            $rawMembers = $first($cells, $mapping['members_count'] ?? []);
            if ($rawMembers !== '') {
                if (preg_match('/\d+/', self::asciiDigits($rawMembers), $m) && (int) $m[0] >= 1 && (int) $m[0] <= self::MAX_MEMBERS) {
                    $members = (int) $m[0];
                } else {
                    $notes[] = 'عدد الأفراد غير صالح («' . $rawMembers . '») فسُجّل 1';
                }
            }

            if ($nid !== null) {
                if (isset($existingFamilyIds[$nid])) {
                    $skipped[] = ['row' => $no, 'name' => $name, 'reason' => 'رقم الهوية مسجّل مسبقاً باسم: ' . $existingFamilyIds[$nid]];
                    continue;
                }
                if (isset($seenIds[$nid])) {
                    $skipped[] = ['row' => $no, 'name' => $name, 'reason' => 'رقم الهوية مكرر داخل الملف (أول ظهور بالصف ' . $seenIds[$nid] . ')'];
                    continue;
                }
                $seenIds[$nid] = $no;
                $seenNames[$nameKey] = $no;

                $records[] = [
                    'row' => $no,
                    'type' => 'family',
                    'name' => $name,
                    'national_id' => $nid,
                    'wife_name' => $wifeName !== '' ? $wifeName : null,
                    'wife_national_id' => $wifeNid,
                    'members_count' => $members,
                    'notes' => $notes,
                ];
                continue;
            }

            // بدون رقم هوية صالح: حساب فقط
            if (isset($existingNameKeys[$nameKey])) {
                $skipped[] = ['row' => $no, 'name' => $name, 'reason' => 'يوجد حساب بنفس الاسم مسبقاً'];
                continue;
            }
            if (isset($seenNames[$nameKey])) {
                $skipped[] = ['row' => $no, 'name' => $name, 'reason' => 'اسم مكرر داخل الملف (أول ظهور بالصف ' . $seenNames[$nameKey] . ')'];
                continue;
            }
            $seenNames[$nameKey] = $no;

            if ($wifeName !== '' || $wifeNid !== null || $rawMembers !== '') {
                $notes[] = 'بيانات الأسرة تُجوهلت لعدم وجود رقم هوية صالح';
            }

            $records[] = [
                'row' => $no,
                'type' => 'user_only',
                'name' => $name,
                'national_id' => null,
                'wife_name' => null,
                'wife_national_id' => null,
                'members_count' => 1,
                'notes' => $notes,
            ];
        }

        return ['records' => $records, 'skipped' => $skipped];
    }

    /** نفس buildRecords لكن مع جلب المكرر من قاعدة البيانات. */
    public static function preview(array $rows, array $mapping): array
    {
        $ids = [];
        $names = [];
        foreach ($rows as $row) {
            $idx = $mapping['national_id'][0] ?? null;
            if ($idx !== null) {
                [$nid] = self::cleanNationalId((string) ($row['cells'][$idx] ?? ''));
                if ($nid !== null) {
                    $ids[$nid] = true;
                }
            }
            $parts = [];
            foreach ($mapping['full_name'] ?? [] as $i) {
                $v = self::cleanName((string) ($row['cells'][$i] ?? ''));
                if ($v !== '') {
                    $parts[] = $v;
                }
            }
            if ($parts) {
                $names[implode(' ', $parts)] = true;
            }
        }

        $existingIds = [];
        foreach (array_chunk(array_keys($ids), 500) as $chunk) {
            foreach (Family::whereIn('national_id', array_map('strval', $chunk))->get(['national_id', 'full_name']) as $f) {
                $existingIds[(string) $f->national_id] = $f->full_name;
            }
        }

        $existingNames = [];
        foreach (array_chunk(array_keys($names), 500) as $chunk) {
            foreach (User::where('role', 'user')->whereIn('name', $chunk)->pluck('name') as $n) {
                $existingNames[self::normalizeAr($n)] = true;
            }
        }

        return self::buildRecords($rows, $mapping, $existingIds, $existingNames);
    }

    // ------------------------------------------------------------- التنفيذ

    /**
     * ينشئ حساباً (وأسرة إن وُجدت) لسجل واحد.
     * @return array ['ok'=>true,'username'=>..,'password'=>..] أو ['ok'=>false,'reason'=>..]
     */
    public static function createOne(array $rec, int $adminId): array
    {
        $plain = Str::password(10, symbols: false);

        try {
            return DB::transaction(function () use ($rec, $plain, $adminId) {
                if ($rec['type'] === 'family' && Family::where('national_id', $rec['national_id'])->exists()) {
                    return ['ok' => false, 'reason' => 'رقم الهوية صار مسجّلاً قبل التنفيذ'];
                }

                $username = self::uniqueUsername($rec['name']);

                $user = User::create([
                    'name' => $rec['name'],
                    'username' => $username,
                    'email' => $username . '@camp.local',
                    'password' => Hash::driver('bcrypt')->make($plain, ['rounds' => 10]),
                    'role' => 'user',
                ]);

                if ($rec['type'] === 'family') {
                    Family::create([
                        'user_id' => $user->id,
                        'full_name' => $rec['name'],
                        'national_id' => $rec['national_id'],
                        'wife_name' => $rec['wife_name'],
                        'wife_national_id' => $rec['wife_national_id'],
                        'members_count' => $rec['members_count'],
                        'status' => 'pending',
                    ]);
                }

                return ['ok' => true, 'username' => $username, 'password' => $plain];
            });
        } catch (Throwable $e) {
            Log::warning('people-import row failed', ['row' => $rec['row'] ?? null, 'error' => $e->getMessage()]);

            return ['ok' => false, 'reason' => 'تعذّر الحفظ (راجع storage/logs/laravel.log)'];
        }
    }

    /** يوزر قصير: أول كلمة من الاسم + 4 أرقام، مع التأكد من عدم التكرار. */
    public static function uniqueUsername(string $name): string
    {
        $firstWord = explode(' ', trim($name))[0] ?? '';
        $base = Str::lower(Str::slug($firstWord, ''));
        $base = $base !== '' ? Str::limit($base, 12, '') : 'user';

        for ($i = 0; $i < 30; $i++) {
            $candidate = $base . random_int(1000, 9999);
            if (! User::where('username', $candidate)->exists()) {
                return $candidate;
            }
        }

        return $base . random_int(100000, 999999);
    }
}
