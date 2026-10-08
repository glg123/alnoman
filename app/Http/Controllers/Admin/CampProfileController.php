<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CampProfile;
use App\Models\Family;
use App\Services\Import\PeopleImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CampProfileController extends Controller
{
    private const NUMERIC = ['displaced_count', 'families_count', 'phone', 'rep_phone', 'rep_phone_alt', 'rep_national_id'];

    public function edit()
    {
        return view('admin.camp-profile.edit', [
            'camp' => CampProfile::query()->first() ?? new CampProfile,
            'registered' => [
                'families' => Family::approved()->count(),
                'members' => (int) Family::approved()->sum('members_count'),
            ],
        ]);
    }

    public function update(Request $request)
    {
        // تحويل الأرقام العربية (٠١٢...) إلى لاتينية قبل التحقق
        $normalized = [];
        foreach (self::NUMERIC as $key) {
            $v = $request->input($key);
            if (is_string($v)) {
                $normalized[$key] = PeopleImporter::asciiDigits(trim($v));
            }
        }
        $request->merge($normalized);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'established_at' => ['nullable', 'date'],
            'displaced_count' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'families_count' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'governorate' => ['nullable', 'string', 'max:100'],
            'area' => ['nullable', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'map_url' => ['nullable', 'url', 'max:500'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'rep_name' => ['nullable', 'string', 'max:150'],
            'rep_phone' => ['nullable', 'string', 'max:30'],
            'rep_phone_alt' => ['nullable', 'string', 'max:30'],
            'rep_email' => ['nullable', 'email', 'max:150'],
            'rep_national_id' => ['nullable', 'regex:/^\d{5,20}$/'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'اسم المخيم مطلوب.',
            'displaced_count.integer' => 'عدد النازحين لازم يكون رقماً.',
            'families_count.integer' => 'عدد الأسر لازم يكون رقماً.',
            'map_url.url' => 'رابط الموقع غير صالح (لازم يبدأ بـ https://).',
            'email.email' => 'البريد الإلكتروني غير صالح.',
            'rep_email.email' => 'بريد المندوب غير صالح.',
            'rep_national_id.regex' => 'رقم هوية المندوب لازم يكون أرقاماً فقط (5 إلى 20 رقم).',
            'logo.image' => 'الشعار لازم يكون صورة.',
            'logo.mimes' => 'صيغ الشعار المسموحة: jpg أو png أو webp.',
            'logo.max' => 'حجم الشعار أكبر من 2 ميغابايت.',
        ]);

        $profile = CampProfile::query()->first() ?? new CampProfile;
        $disk = Storage::disk('public');

        if ($request->hasFile('logo')) {
            if ($profile->logo_path) {
                $disk->delete($profile->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('camp', 'public');
        } elseif ($request->boolean('remove_logo') && $profile->logo_path) {
            $disk->delete($profile->logo_path);
            $data['logo_path'] = null;
        }

        unset($data['logo'], $data['remove_logo']);

        $profile->fill($data)->save();

        return redirect()->route('admin.camp-profile.edit')->with('success', 'تم حفظ الإعدادات العامة.');
    }
}
