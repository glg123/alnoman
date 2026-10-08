<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $users = User::where('role', 'user')
            ->with('family')
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    /**
     * إنشاء مستخدم جديد مع توليد يوزر نيم وباسورد تلقائياً،
     * ليتم نسخها/إرسالها للمستخدم النهائي.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $username = $this->generateUniqueUsername($data['name']);
        $plainPassword = Str::password(10, symbols: false);

        $user = User::create([
            'name'     => $data['name'],
            'username' => $username,
            'email'    => $username . '@camp.local', // بريد داخلي وهمي لتفادي قيد unique
            'password' => Hash::make($plainPassword),
            'role'     => 'user',
        ]);

        ActivityLog::record('user.created', "أنشأ حساب المستخدم {$user->name} ({$user->username})");

        return view('admin.users.created', [
            'user'          => $user,
            'plainPassword' => $plainPassword,
        ]);
    }

    /** إيقاف أو تفعيل حساب مستخدم. الإيقاف يطرد جلساته الحالية فوراً. */
    public function toggleActive(User $user)
    {
        abort_if($user->isAdmin(), 403);

        $user->is_active = ! $user->is_active;
        $user->save();

        if (! $user->is_active) {
            $this->killSessions($user);
        }

        ActivityLog::record(
            $user->is_active ? 'user.activated' : 'user.deactivated',
            ($user->is_active ? 'فعّل' : 'أوقف') . " حساب {$user->name} ({$user->username})"
        );

        return back()->with('success', $user->is_active ? 'تم تفعيل الحساب.' : 'تم إيقاف الحساب.');
    }

    /** يولّد كلمة مرور جديدة ويعرضها مرة وحدة للأدمن. */
    public function resetPassword(User $user)
    {
        abort_if($user->isAdmin(), 403);

        $plainPassword = Str::password(10, symbols: false);

        $user->forceFill(['password' => Hash::make($plainPassword)])->save();
        $this->killSessions($user);

        ActivityLog::record('user.password_reset', "أعاد تعيين كلمة مرور {$user->name} ({$user->username})");

        return view('admin.users.password-reset', [
            'user'          => $user,
            'plainPassword' => $plainPassword,
        ]);
    }

    /** يلغي جلسات المستخدم المفتوحة وتذكّر الدخول (الجلسات بقاعدة البيانات فقط). */
    protected function killSessions(User $user): void
    {
        $user->forceFill(['remember_token' => Str::random(60)])->save();

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }
    }

    protected function generateUniqueUsername(string $name): string
    {
        $base = Str::slug($name, '') ?: 'user';
        $base = Str::lower($base);
        $username = $base;
        $i = 1;

        while (User::where('username', $username)->exists()) {
            $username = $base . $i;
            $i++;
        }

        return $username;
    }
}
