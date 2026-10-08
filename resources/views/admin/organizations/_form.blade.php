<form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="bg-white border border-line rounded-2xl p-6 space-y-4">
    @csrf
    @if($formMethod === 'PUT') @method('PUT') @endif

    @if(isset($organization) && $organization->logo_url)
        <div class="flex items-center gap-3 mb-2">
            <img src="{{ $organization->logo_url }}" alt="{{ $organization->name }}" class="w-14 h-14 rounded-xl object-cover border border-line">
            <p class="text-xs text-muted">الشعار الحالي — اختر صورة جديدة أدناه فقط إن أردت استبداله</p>
        </div>
    @endif

    <div>
        <label class="block text-sm font-semibold mb-1.5">اسم المؤسسة</label>
        <input type="text" name="name" value="{{ old('name', $organization->name ?? '') }}" required autofocus
               class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
        @error('name') <p class="text-rejected text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-semibold mb-1.5">الشعار (اختياري)</label>
        @include('family._upload-field', ['name' => 'logo', 'accept' => 'image/*', 'hint' => 'JPG, PNG — حتى 2 ميجابايت'])
        @error('logo') <p class="text-rejected text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-semibold mb-1.5">رقم الهاتف (اختياري)</label>
            <input type="text" name="phone" value="{{ old('phone', $organization->phone ?? '') }}"
                   class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1.5">العنوان (اختياري)</label>
            <input type="text" name="address" value="{{ old('address', $organization->address ?? '') }}"
                   class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
        </div>
    </div>

    <div>
        <label class="block text-sm font-semibold mb-1.5">وصف مختصر (اختياري)</label>
        <textarea name="description" rows="3"
                  class="w-full rounded-lg border border-line px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">{{ old('description', $organization->description ?? '') }}</textarea>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="bg-primary hover:bg-primary-dark transition text-white font-semibold rounded-lg px-6 py-2.5 text-sm">
            {{ $submitLabel }}
        </button>
        <a href="{{ route('admin.organizations.index') }}" class="text-sm font-semibold text-muted hover:text-ink px-5 py-2.5">إلغاء</a>
    </div>
</form>
