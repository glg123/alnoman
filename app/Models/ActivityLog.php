<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'action', 'description', 'ip_address', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public const ACTIONS = [
        'auth.login'              => 'تسجيل دخول',
        'family.approved'         => 'اعتماد أسرة',
        'family.rejected'         => 'رفض أسرة',
        'edit.approved'           => 'اعتماد طلب تعديل',
        'edit.rejected'           => 'رفض طلب تعديل',
        'user.created'            => 'إنشاء مستخدم',
        'user.activated'          => 'تفعيل حساب',
        'user.deactivated'        => 'إيقاف حساب',
        'user.password_reset'     => 'إعادة تعيين كلمة مرور',
        'account.password_changed' => 'تغيير كلمة المرور',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** يسجّل حدثاً. فشل التسجيل ما بيوقّف العملية الأساسية أبداً. */
    public static function record(string $action, string $description): void
    {
        try {
            static::create([
                'user_id'     => Auth::id(),
                'action'      => $action,
                'description' => mb_substr($description, 0, 500),
                'ip_address'  => request()->ip(),
                'created_at'  => now(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
