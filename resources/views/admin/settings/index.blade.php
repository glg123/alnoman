@extends('layouts.app')
@section('title', 'إعدادات المخيم')

@section('content')
    <h1 class="text-xl font-bold mb-6">إعدادات المخيم</h1>

    {{-- فورم تعديل قيم كل الحقول (أساسية + ديناميكية) --}}
    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data"
          class="bg-white border border-line rounded-2xl p-5 sm:p-6 space-y-5">
        @csrf
        @method('PUT')

        @foreach($settings as $setting)
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-sm font-semibold">{{ $setting->label }}</label>
                    @unless($setting->is_system)
                        <form method="POST" action="{{ route('admin.settings.fields.destroy', $setting) }}"
                              onsubmit="return confirm('حذف هذا الحقل نهائياً؟')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-muted hover:text-rejected font-semibold">حذف الحقل</button>
                        </form>
                    @endunless
                </div>

                @switch($setting->type)
                    @case('textarea')
                        <textarea name="{{ $setting->key }}" rows="3"
                                  class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">{{ old($setting->key, $setting->value) }}</textarea>
                        @break

                    @case('image')
                        <div class="flex items-center gap-4">
                            @if($setting->value)
                                <img src="{{ Storage::url($setting->value) }}" alt="{{ $setting->label }}"
                                     class="w-16 h-16 rounded-lg object-cover border border-line">
                            @endif
                            <input type="file" name="{{ $setting->key }}" accept="image/*"
                                   class="text-sm text-muted file:ml-3 file:rounded-lg file:border-0 file:bg-canvas file:px-4 file:py-2 file:text-sm file:font-semibold file:text-ink hover:file:bg-line/60">
                        </div>
                        @break

                    @case('number')
                        <input type="number" name="{{ $setting->key }}" value="{{ old($setting->key, $setting->value) }}"
                               class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                        @break

                    @case('date')
                        <input type="date" name="{{ $setting->key }}" value="{{ old($setting->key, $setting->value) }}"
                               class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                        @break

                    @case('url')
                        <input type="url" name="{{ $setting->key }}" value="{{ old($setting->key, $setting->value) }}" dir="ltr"
                               class="w-full rounded-lg border border-line px-4 py-2.5 text-sm text-left focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                        @break

                    @default
                        <input type="text" name="{{ $setting->key }}" value="{{ old($setting->key, $setting->value) }}"
                               class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                @endswitch

                @error($setting->key) <p class="text-rejected text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        @endforeach

        <div class="pt-2">
            <button type="submit"
                    class="bg-primary hover:bg-primary-dark transition text-white font-semibold rounded-lg px-6 py-2.5 text-sm">
                حفظ الإعدادات
            </button>
        </div>
    </form>

    {{-- إضافة حقل جديد ديناميكياً --}}
    <div class="bg-white border border-line rounded-2xl p-5 sm:p-6 mt-6">
        <h2 class="font-bold text-base mb-1">إضافة حقل جديد</h2>
        <p class="text-sm text-muted mb-5">بيضاف مباشرة كحقل تعبّي قيمته فوق ضمن فورم الإعدادات.</p>

        <form method="POST" action="{{ route('admin.settings.fields.store') }}" class="grid sm:grid-cols-3 gap-4 items-end">
            @csrf
            <div class="sm:col-span-2">
                <label class="block text-sm font-semibold mb-1.5">اسم الحقل</label>
                <input type="text" name="label" required placeholder="مثال: رقم هاتف الإدارة"
                       class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                @error('label') <p class="text-rejected text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1.5">نوع الحقل</label>
                <select name="type" required
                        class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    @foreach($types as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-3">
                <button type="submit"
                        class="bg-white border border-line hover:border-primary/40 transition text-sm font-semibold rounded-lg px-5 py-2.5">
                    + إضافة الحقل
                </button>
            </div>
        </form>
    </div>
@endsection
