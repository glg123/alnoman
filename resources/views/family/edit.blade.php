@extends('layouts.app')
@section('title', 'تعديل بيانات الأسرة')

@section('content')
    <div class="mb-6">
        <h1 class="text-xl font-bold">تعديل بيانات الأسرة</h1>
        @if($family->status === 'approved')
            <p class="text-sm text-muted mt-1">بياناتك معتمدة حالياً — أي تعديل سيُرسل كطلب جديد بانتظار موافقة الإدارة، ولن يُطبّق فوراً.</p>
        @else
            <p class="text-sm text-muted mt-1">لم تُعتمد بياناتك بعد، يمكنك تعديلها مباشرة.</p>
        @endif
    </div>

    @php $formAction = route('family.update'); $formMethod = 'PUT'; $submitLabel = $family->status === 'approved' ? 'إرسال طلب التعديل' : 'حفظ التعديلات'; @endphp
    @include('family._form')
@endsection
