<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CampSetting extends Model
{
    protected $fillable = [
        'key',
        'label',
        'type',
        'value',
        'is_system',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    /** مخزَّن مؤقتاً ضمن نفس الطلب فقط، حتى ما نستعلم عن الإعدادات أكتر من مرة بنفس الصفحة */
    protected static ?Collection $mapCache = null;

    public static function map(): Collection
    {
        return static::$mapCache ??= static::query()->pluck('value', 'key');
    }

    public static function value(string $key, $default = null)
    {
        return static::map()[$key] ?? $default;
    }

    public static function flushCache(): void
    {
        static::$mapCache = null;
    }

    /** الأنواع المتاحة عند إضافة حقل ديناميكي جديد من واجهة الإعدادات */
    public static function availableTypes(): array
    {
        return [
            'text'     => 'نص قصير',
            'textarea' => 'نص طويل',
            'number'   => 'رقم',
            'url'      => 'رابط',
            'date'     => 'تاريخ',
            'image'    => 'صورة',
        ];
    }
}
