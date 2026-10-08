@extends('layouts.app')
@section('title', 'إدخال بيانات الأسرة')

@section('content')
    <div class="mb-6">
        <h1 class="text-xl font-bold">إدخال بيانات الأسرة</h1>
        <p class="text-sm text-muted mt-1">عبّئ البيانات بدقة — ستُراجَع من إدارة المخيم قبل اعتمادها.</p>
    </div>

    @php $formAction = route('family.store'); $formMethod = 'POST'; $submitLabel = 'إرسال البيانات'; @endphp
    @include('family._form')
@endsection
