@extends('layouts.app')
@section('title', 'الإعدادات العامة')

@section('content')
    <div class="mb-6">
        <h1 class="text-xl font-bold mb-1">الإعدادات العامة</h1>
        <p class="text-sm text-muted">بيانات المخيم الأساسية. الشعار والاسم بيظهروا بالقائمة الجانبية.</p>
    </div>

    @if($errors->any())
        <div class="mb-5 rounded-lg border border-rejected/30 bg-rejected/10 text-rejected px-4 py-3 text-sm font-medium">
            في حقول تحتاج تصحيح، راجع الرسائل بالأحمر تحت كل حقل.
        </div>
    @endif

    <form method="POST" action="{{ route('admin.camp-profile.update') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf
        @method('PUT')

        {{-- بيانات المخيم --}}
        <section class="bg-white border border-line rounded-2xl p-6 space-y-5">
            <h2 class="font-bold">بيانات المخيم</h2>

            <div class="flex flex-wrap items-start gap-5">
                <div class="shrink-0">
                    <div class="w-28 h-28 rounded-2xl border border-line bg-canvas flex items-center justify-center overflow-hidden">
                        <img id="logo-preview" src="{{ $camp->logo_url }}" alt="" class="w-full h-full object-contain {{ $camp->logo_url ? '' : 'hidden' }}">
                        <span id="logo-empty" class="text-xs text-muted text-center px-2 {{ $camp->logo_url ? 'hidden' : '' }}">لا يوجد شعار</span>
                    </div>
                </div>
                <div class="flex-1 min-w-[220px]">
                    <label class="block text-sm font-semibold mb-1.5">شعار المخيم</label>
                    <input type="file" name="logo" id="logo-input" accept=".jpg,.jpeg,.png,.webp"
                           class="block w-full text-sm border border-line rounded-lg p-2 file:ml-3 file:rounded-md file:border-0 file:bg-primary file:text-white file:px-4 file:py-1.5 file:text-sm file:font-semibold">
                    <p class="text-xs text-muted mt-1.5">jpg أو png أو webp، حتى 2 ميغابايت. يفضّل صورة مربعة.</p>
                    @error('logo') <p class="text-rejected text-xs mt-1">{{ $message }}</p> @enderror
                    @if($camp->logo_path)
                        <label class="flex items-center gap-2 text-sm mt-3 cursor-pointer">
                            <input type="checkbox" name="remove_logo" value="1" class="rounded border-line text-rejected focus:ring-rejected">
                            <span>حذف الشعار الحالي</span>
                        </label>
                    @endif
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1.5">اسم المخيم <span class="text-rejected">*</span></label>
                <input type="text" name="name" value="{{ old('name', $camp->name) }}" required
                       class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                @error('name') <p class="text-rejected text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-semibold mb-1.5">تاريخ إنشاء المخيم</label>
                    <input type="date" name="established_at" value="{{ old('established_at', $camp->established_at?->format('Y-m-d')) }}"
                           class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    @error('established_at') <p class="text-rejected text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1.5">نبذة عن المخيم</label>
                <textarea name="description" rows="3"
                          class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">{{ old('description', $camp->description) }}</textarea>
                @error('description') <p class="text-rejected text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </section>

        {{-- الأعداد --}}
        <section class="bg-white border border-line rounded-2xl p-6 space-y-5">
            <h2 class="font-bold">الأعداد</h2>
            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-semibold mb-1.5">عدد النازحين في المخيم</label>
                    <input type="text" inputmode="numeric" name="displaced_count" value="{{ old('displaced_count', $camp->displaced_count) }}"
                           class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    @error('displaced_count') <p class="text-rejected text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1.5">عدد الأسر في المخيم</label>
                    <input type="text" inputmode="numeric" name="families_count" value="{{ old('families_count', $camp->families_count) }}"
                           class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    @error('families_count') <p class="text-rejected text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <p class="text-xs text-muted bg-canvas rounded-lg px-4 py-3">
                المسجّل حالياً بالنظام (بيانات معتمدة): <span class="font-semibold text-ink">{{ $registered['families'] }}</span> أسرة،
                <span class="font-semibold text-ink">{{ $registered['members'] }}</span> فرد. للمقارنة فقط، والأرقام فوق بتدخلها أنت.
            </p>
        </section>

        {{-- العنوان --}}
        <section class="bg-white border border-line rounded-2xl p-6 space-y-5">
            <h2 class="font-bold">عنوان المخيم</h2>
            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-semibold mb-1.5">المحافظة</label>
                    <input type="text" name="governorate" value="{{ old('governorate', $camp->governorate) }}"
                           class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    @error('governorate') <p class="text-rejected text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1.5">المنطقة / الحي</label>
                    <input type="text" name="area" value="{{ old('area', $camp->area) }}"
                           class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    @error('area') <p class="text-rejected text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1.5">العنوان التفصيلي</label>
                <input type="text" name="address" value="{{ old('address', $camp->address) }}"
                       class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                @error('address') <p class="text-rejected text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1.5">رابط الموقع على الخريطة</label>
                <input type="url" name="map_url" dir="ltr" placeholder="https://maps.google.com/..." value="{{ old('map_url', $camp->map_url) }}"
                       class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                @error('map_url') <p class="text-rejected text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </section>

        {{-- التواصل --}}
        <section class="bg-white border border-line rounded-2xl p-6 space-y-5">
            <h2 class="font-bold">التواصل مع المخيم</h2>
            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-semibold mb-1.5">رقم الهاتف</label>
                    <input type="text" inputmode="tel" dir="ltr" name="phone" value="{{ old('phone', $camp->phone) }}"
                           class="w-full rounded-lg border border-line px-4 py-2.5 text-sm text-right focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    @error('phone') <p class="text-rejected text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1.5">البريد الإلكتروني</label>
                    <input type="email" dir="ltr" name="email" value="{{ old('email', $camp->email) }}"
                           class="w-full rounded-lg border border-line px-4 py-2.5 text-sm text-right focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    @error('email') <p class="text-rejected text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        {{-- مندوب المخيم --}}
        <section class="bg-white border border-line rounded-2xl p-6 space-y-5">
            <h2 class="font-bold">مندوب المخيم</h2>
            <div class="grid sm:grid-cols-2 gap-5">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-semibold mb-1.5">اسم المندوب</label>
                    <input type="text" name="rep_name" value="{{ old('rep_name', $camp->rep_name) }}"
                           class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    @error('rep_name') <p class="text-rejected text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1.5">رقم الجوال</label>
                    <input type="text" inputmode="tel" dir="ltr" name="rep_phone" value="{{ old('rep_phone', $camp->rep_phone) }}"
                           class="w-full rounded-lg border border-line px-4 py-2.5 text-sm text-right focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    @error('rep_phone') <p class="text-rejected text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1.5">رقم جوال بديل</label>
                    <input type="text" inputmode="tel" dir="ltr" name="rep_phone_alt" value="{{ old('rep_phone_alt', $camp->rep_phone_alt) }}"
                           class="w-full rounded-lg border border-line px-4 py-2.5 text-sm text-right focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    @error('rep_phone_alt') <p class="text-rejected text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1.5">البريد الإلكتروني</label>
                    <input type="email" dir="ltr" name="rep_email" value="{{ old('rep_email', $camp->rep_email) }}"
                           class="w-full rounded-lg border border-line px-4 py-2.5 text-sm text-right focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    @error('rep_email') <p class="text-rejected text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1.5">رقم الهوية</label>
                    <input type="text" inputmode="numeric" name="rep_national_id" value="{{ old('rep_national_id', $camp->rep_national_id) }}"
                           class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    @error('rep_national_id') <p class="text-rejected text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <button type="submit"
                class="bg-primary hover:bg-primary-dark transition text-white font-semibold rounded-lg px-8 py-2.5 text-sm">
            حفظ الإعدادات
        </button>
    </form>

    <script>
        (function () {
            var input = document.getElementById('logo-input');
            var img = document.getElementById('logo-preview');
            var empty = document.getElementById('logo-empty');
            input.addEventListener('change', function () {
                var f = input.files && input.files[0];
                if (!f) return;
                img.src = URL.createObjectURL(f);
                img.classList.remove('hidden');
                empty.classList.add('hidden');
            });
        })();
    </script>
@endsection
