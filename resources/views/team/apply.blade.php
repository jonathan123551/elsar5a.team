@extends('layouts.app')

@section('content')

<style>
    /* ===== Team Application Form Fix ===== */

    .team-form {
        max-width: 800px;
        margin: 40px auto;
    }

    .team-form input,
    .team-form textarea,
    .team-form select {
        width: 100%;
        padding: 12px 14px;
        margin-bottom: 15px;
        border-radius: 6px;
        border: 1px solid #ccc;
        background-color: #ffffff;
        color: #000000;
        font-size: 16px;
    }

    .team-form input::placeholder,
    .team-form textarea::placeholder {
        color: #555555;
    }

    .team-form input:focus,
    .team-form textarea:focus,
    .team-form select:focus {
        outline: none;
        border-color: #f5c542;
        color: #000000;
        background-color: #ffffff;
    }

    .team-form label {
        display: block;
        margin-bottom: 6px;
        font-weight: bold;
        color: #000000;
    }

    .team-form button {
        background-color: #f5c542;
        color: #000;
        border: none;
        padding: 14px;
        width: 100%;
        font-size: 18px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
    }

    .team-form button:hover {
        background-color: #e0b838;
    }

</style>

<div class="team-form">
    <h2 class="text-center mb-4">
        التقديم لفريق الصرخة المسرحي 🎭
    </h2>

    <form method="POST" action="{{ route('team.apply.store') }}">
        @csrf

        <label>الاسم</label>
        <input type="text" name="name" placeholder="اكتب اسمك" required>

        <label>رقم التليفون</label>
        <input type="text" name="phone" placeholder="01XXXXXXXXX" required>

        <label>الإيميل</label>
        <input type="email" name="email" placeholder="example@email.com" required>

        <label>السن</label>
        <input type="number" name="age" placeholder="السن" required>

        <label>المرحلة الدراسية</label>
        <select name="education_level" required>
            <option value="">اختر المرحلة</option>
            <option value="اعدادي">إعدادي</option>
            <option value="ثانوي">ثانوي</option>
            <option value="جامعة">جامعة</option>
            <option value="خريجين">خريجين</option>
        </select>

        <label>المدرسة / الكلية</label>
        <input type="text" name="school" placeholder="اسم المدرسة أو الكلية">

        <label>العنوان</label>
        <input type="text" name="address" placeholder="العنوان">

        <label>أب الاعتراف</label>
        <input type="text" name="confession_father" placeholder="اسم أب الاعتراف">

        <label>مشترك في خدمات إيه داخل أو خارج الكنيسة؟</label>
        <textarea name="services" rows="3" placeholder="اكتب الخدمات"></textarea>

        <label>هل التحقت بفصل إعداد خدام؟</label>
        <select name="servants_class">
            <option value="لا">لا</option>
            <option value="نعم">نعم</option>
        </select>

        <label>القسم اللي حابب تشترك فيه</label>
        <select name="department" required>
            <option value="">اختر القسم</option>
            <option value="تمثيل وإخراج">تمثيل وإخراج</option>
            <option value="سينوغرافيا">سينوغرافيا</option>
            <option value="تأليف">تأليف</option>
        </select>

        <label>ليه حابب تنضم لفريق الصرخة؟</label>
        <textarea name="reason" rows="4" placeholder="اكتب سبب انضمامك"></textarea>

        <button type="submit">إرسال الطلب</button>
    </form>
</div>

@endsection
