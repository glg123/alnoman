<?php

namespace App\Support;

use App\Models\Family;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * كل ملفات الأسر (صور الهوية والمرفقات) تُخزَّن على القرص الخاص (local)
 * ولا تُعرض إلا عبر FileController بعد فحص الصلاحية.
 */
final class CampFiles
{
    public const DISK = 'local';

    public static function disk(): Filesystem
    {
        return Storage::disk(self::DISK);
    }

    /** كل مسارات الملفات المستخدمة حالياً لدى الأسرة. */
    public static function forFamily(Family $family): array
    {
        $family->loadMissing('specialCases');

        return self::clean(array_merge(
            [$family->national_id_photo_path],
            $family->specialCases->pluck('document_path')->all()
        ));
    }

    /** مسارات الملفات داخل payload طلب تعديل. */
    public static function forPayload(?array $payload): array
    {
        if (! $payload) {
            return [];
        }

        return self::clean(array_merge(
            [$payload['national_id_photo_path'] ?? null],
            array_column($payload['special_cases'] ?? [], 'document_path')
        ));
    }

    /** يحذف من $candidates كل ما ليس موجوداً في $keep. */
    public static function deleteExcept(array $candidates, array $keep): void
    {
        $orphans = array_values(array_diff(self::clean($candidates), self::clean($keep)));

        if ($orphans) {
            self::disk()->delete($orphans);
        }
    }

    private static function clean(array $paths): array
    {
        return array_values(array_unique(array_filter($paths, fn ($p) => is_string($p) && $p !== '')));
    }
}
