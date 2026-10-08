@extends('layouts.app')
@section('title', 'إضافة مؤسسة')

@section('content')
    <div class="max-w-lg">
        <h1 class="font-display text-xl font-bold mb-6">إضافة مؤسسة جديدة</h1>
        @php $formAction = route('admin.organizations.store'); $formMethod = 'POST'; $submitLabel = 'حفظ المؤسسة'; @endphp
        @include('admin.organizations._form')
    </div>
@endsection
