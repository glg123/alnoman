@extends('layouts.app')
@section('title', 'تعديل بيانات المؤسسة')

@section('content')
    <div class="max-w-lg">
        <h1 class="font-display text-xl font-bold mb-6">تعديل بيانات {{ $organization->name }}</h1>
        @php $formAction = route('admin.organizations.update', $organization); $formMethod = 'PUT'; $submitLabel = 'حفظ التعديلات'; @endphp
        @include('admin.organizations._form')

        <form method="POST" action="{{ route('admin.organizations.destroy', $organization) }}"
              onsubmit="return confirm('هل أنت متأكد من حذف هذه المؤسسة؟')" class="mt-4">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-sm font-semibold text-rejected hover:opacity-80">حذف المؤسسة</button>
        </form>
    </div>
@endsection
