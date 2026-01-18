@extends('layouts.app')

@section('title','التقديم لفريق الصرخة')

@section('content')
<section class="max-w-2xl mx-auto space-y-4">

<h1 class="text-2xl font-bold text-center">🎭 التقديم لفريق الصرخة المسرحي</h1>

@if(session('success'))
<div class="bg-emerald-500/10 border border-emerald-400 p-4 rounded-xl text-center">
    <p class="mb-3">تم إرسال طلبك بنجاح ❤️</p>
    <a href="https://chat.whatsapp.com/LRklhOxHEPu3M1UtxsdOvX"
       target="_blank"
       class="px-4 py-2 bg-emerald-400 text-black rounded-full">
        الانضمام لجروب واتساب
    </a>
</div>
@endif

<form method="POST" action="/join-team" class="space-y-3">
@csrf

<input name="full_name" placeholder="الاسم بالكامل" class="input w-full">
<input name="phone" placeholder="رقم التليفون" class="input w-full">
<input name="email" placeholder="الإيميل" class="input w-full">
<input name="age" type="number" placeholder="السن" class="input w-full">

<select name="education_stage" id="education_stage" class="input w-full">
    <option value="">المرحلة الدراسية</option>
    <option value="اعدادي">إعدادي</option>
    <option value="ثانوي">ثانوي</option>
    <option value="جامعة">جامعة</option>
    <option value="خريجين">خريجين</option>
</select>

<input name="school_or_college" placeholder="المدرسة / الكلية" class="input w-full">
<input name="address" placeholder="العنوان" class="input w-full">
<input name="confession_father" placeholder="أب الاعتراف" class="input w-full">

<textarea name="services" placeholder="مشترك في خدمات ايه داخل أو خارج الكنيسة" class="input w-full"></textarea>

<select name="preparation_class" class="input w-full">
    <option value="">هل التحقت بفصل إعداد خدام؟</option>
    <option value="1">نعم</option>
    <option value="0">لا</option>
</select>

<select name="department" id="department" class="input w-full">
    <option value="">القسم اللي حابب تشترك فيه</option>
</select>

<textarea name="why_join" placeholder="ليه حابب تنضم لفريق الصرخة؟" class="input w-full"></textarea>

<button class="w-full bg-amber-400 text-black py-2 rounded-full">
    إرسال الطلب
</button>
</form>
</section>

<script>
const stage = document.getElementById('education_stage');
const department = document.getElementById('department');

stage.addEventListener('change', function () {
    department.innerHTML = '<option value="">اختر القسم</option>';

    if (this.value === 'اعدادي') {
        department.innerHTML += `
            <option value="تمثيل واخراج">تمثيل وإخراج</option>
            <option value="سينوغرافيا">سينوغرافيا</option>
        `;
    }

    else if (this.value === 'ثانوي' || this.value === 'جامعة') {
        department.innerHTML += `
            <option value="تمثيل واخراج">تمثيل وإخراج</option>
            <option value="سينوغرافيا">سينوغرافيا</option>
            <option value="تاليف">تأليف</option>
        `;
    }

    else if (this.value === 'خريجين') {
        department.innerHTML += `
            <option value="تاليف">تأليف</option>
        `;
    }
});
</script>
@endsection
