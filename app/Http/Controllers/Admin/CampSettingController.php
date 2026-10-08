<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CampSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CampSettingController extends Controller
{
    public function index()
    {
        $settings = CampSetting::orderBy('sort_order')->orderBy('id')->get();

        return view('admin.settings.index', [
            'settings' => $settings,
            'types'    => CampSetting::availableTypes(),
        ]);
    }

    /** حفظ قيم كل الحقول (الأساسية + الديناميكية) دفعة وحدة من نفس الفورم */
    public function update(Request $request)
    {
        $settings = CampSetting::all();

        foreach ($settings as $setting) {
            if ($setting->type === 'image') {
                if ($request->hasFile($setting->key)) {
                    $request->validate([
                        $setting->key => ['image', 'max:2048'],
                    ]);

                    if ($setting->value) {
                        Storage::disk('public')->delete($setting->value);
                    }

                    $setting->value = $request->file($setting->key)->store('camp-settings', 'public');
                    $setting->save();
                }
                continue;
            }

            if ($request->has($setting->key)) {
                $rules = match ($setting->type) {
                    'number' => ['nullable', 'numeric'],
                    'url'    => ['nullable', 'url'],
                    'date'   => ['nullable', 'date'],
                    default  => ['nullable', 'string', 'max:2000'],
                };

                $data = $request->validate([$setting->key => $rules]);
                $setting->update(['value' => $data[$setting->key]]);
            }
        }

        CampSetting::flushCache();

        return back()->with('success', 'تم حفظ إعدادات المخيم.');
    }

    /** إضافة حقل جديد ديناميكياً (اسم الحقل + نوعه) يصير مباشرة ضمن فورم الإعدادات */
    public function storeField(Request $request)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'type'  => ['required', Rule::in(array_keys(CampSetting::availableTypes()))],
        ]);

        $baseKey = Str::slug($data['label'], '_');
        $key = $baseKey;
        $i = 2;
        while (CampSetting::where('key', $key)->exists()) {
            $key = "{$baseKey}_{$i}";
            $i++;
        }

        CampSetting::create([
            'key'        => $key,
            'label'      => $data['label'],
            'type'       => $data['type'],
            'is_system'  => false,
            'sort_order' => (CampSetting::max('sort_order') ?? 0) + 1,
        ]);

        CampSetting::flushCache();

        return back()->with('success', 'تمت إضافة الحقل الجديد. عبّي قيمته واحفظ الإعدادات.');
    }

    /** حذف حقل ديناميكي (الحقول الأساسية محمية ولا يمكن حذفها) */
    public function destroyField(CampSetting $field)
    {
        abort_if($field->is_system, 403, 'لا يمكن حذف حقل أساسي.');

        if ($field->type === 'image' && $field->value) {
            Storage::disk('public')->delete($field->value);
        }

        $field->delete();
        CampSetting::flushCache();

        return back()->with('success', 'تم حذف الحقل.');
    }
}
