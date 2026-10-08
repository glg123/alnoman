<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * بيانات المخيم العامة (سجل واحد فقط).
 */
class CampProfile extends Model
{
    protected $table = 'camp_profile';

    protected $fillable = [
        'name', 'logo_path', 'description', 'established_at',
        'displaced_count', 'families_count',
        'governorate', 'area', 'address', 'map_url',
        'phone', 'email',
        'rep_name', 'rep_phone', 'rep_phone_alt', 'rep_email', 'rep_national_id',
    ];

    protected function casts(): array
    {
        return [
            'established_at' => 'date',
        ];
    }

    private static ?self $cached = null;

    /** السجل الحالي، أو نموذج فارغ إذا لم يُحفظ شيء بعد (أو لم يتم تشغيل الـ migration). */
    public static function current(): self
    {
        if (self::$cached) {
            return self::$cached;
        }

        if (! Schema::hasTable('camp_profile')) {
            return new self;
        }

        return self::$cached = (static::query()->first() ?? new self);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }
}
