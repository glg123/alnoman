@extends('layouts.app')
@section('title', 'استيراد من Excel')

@section('content')
    <div class="max-w-2xl">
        <h1 class="text-xl font-bold mb-1">استيراد من Excel</h1>
        <p class="text-sm text-muted mb-6">ارفع ملف فيه أسماء الأشخاص. بالخطوة التالية بتحدد أي عمود بالملف يقابل أي حقل بالنظام، حتى لو أعمدة ملفك مختلفة أو ناقصة أو زايدة.</p>

        <form method="POST" action="{{ route('admin.import.upload') }}" enctype="multipart/form-data"
              class="bg-white border border-line rounded-2xl p-6 space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-semibold mb-1.5">ملف Excel أو CSV</label>
                <input type="file" name="file" required accept=".xlsx,.csv,.txt"
                       class="block w-full text-sm border border-line rounded-lg p-2.5 file:ml-3 file:rounded-md file:border-0 file:bg-primary file:text-white file:px-4 file:py-2 file:text-sm file:font-semibold">
                @error('file') <p class="text-rejected text-xs mt-1.5">{{ $message }}</p> @enderror
                <p class="text-xs text-muted mt-1.5">الصيغ: xlsx أو csv. الحد الأقصى 5 ميغابايت و 10,000 صف.</p>
            </div>

            <label class="flex items-center gap-2 text-sm cursor-pointer">
                <input type="checkbox" name="no_header" value="1" class="rounded border-line text-primary focus:ring-primary">
                <span>الملف ما فيه صف عناوين (الصف الأول بيانات)</span>
            </label>

            <button type="submit"
                    class="w-full bg-primary hover:bg-primary-dark transition text-white font-semibold rounded-lg py-2.5 text-sm">
                رفع والمتابعة لربط الأعمدة
            </button>
        </form>

        <div class="mt-6 bg-white border border-line rounded-2xl p-6 text-sm space-y-3">
            <h2 class="font-bold">شو بيصير بكل صف؟</h2>
            <ul class="space-y-2 text-muted leading-relaxed">
                <li>• اسم + رقم هوية صالح: بيُنشأ حساب دخول + أسرة بحالة «قيد المراجعة»، والمستخدم بيكمّل باقي بياناته.</li>
                <li>• اسم فقط (بدون هوية): بيُنشأ حساب دخول فقط.</li>
                <li>• رقم الهوية الموجود مسبقاً أو المكرر بالملف: بيتم تخطي الصف وبيظهر لك السبب.</li>
                <li>• الأعمدة الزائدة بالملف بتتجاهل.</li>
                <li>• اسم المستخدم وكلمة المرور بيتولّدوا تلقائياً وبتنزّلهم بملف Excel بالنهاية.</li>
            </ul>
            <a href="{{ route('admin.import.template') }}" class="inline-block text-primary font-semibold underline underline-offset-4">
                تحميل ملف نموذجي
            </a>
        </div>
    </div>
@endsection
