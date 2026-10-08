<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
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
            ->paginate(20);

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

        return view('admin.users.created', [
            'user'          => $user,
            'plainPassword' => $plainPassword,
        ]);
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
