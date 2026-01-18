@extends('layouts.app')

@section('content')

<style>
    .team-form {
        max-width: 800px;
        margin: 50px auto;
        padding: 30px;
        background: rgba(0, 0, 0, 0.25);
        border-radius: 14px;
        backdrop-filter: blur(6px);
    }

    .team-form h2 {
        color: #ffffff;
        text-align: center;
        margin-bottom: 30px;
    }

    .team-form label {
        color: #ffffff;
        font-weight: 600;
        margin-bottom: 6px;
        display: block;
    }

    .team-form input,
    .team-form textarea,
    .team-form select {
        width: 100%;
        padding: 14px 16px;
        margin-bottom: 18px;
        border-radius: 10px;
        border: 1px solid rgba(255,255,255,0.25);
        background-color: rgba(255,255,255,0.9);
        color: #000;
        font-size: 15px;
    }

    .team-form button {
        width: 100%;
        padding: 15px;
        font-size: 18px;
        font-weight: bold;
        border-radius: 12px;
        border: none;
        background: linear-gradient(135deg, #f5c542, #e0b838);
        cursor: pointer;
    }
</style>

<div class="team-form">
    <h2>التقديم لفريق الصرخة المسرحي 🎭</h2>

    @if(session('success'))
        <div style="
            background: rgba(0,128,0,0.2);
            border: 1px solid #3cff00;
            color: #fff;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;">
            {{ session('success') }}
            <br>
            <small>سيتم تحويلك لجروب واتساب…</small>
        </div>

        <script>
            setTimeout(() => {
                window.location.href = "https://chat.whatsapp.com/LRklhOxHEPu3M1UtxsdOvX";
            }, 3000);
        </script>
    @endif

    <form method="POST" action="{{ route('team.apply.store') }}">
        @csrf

        <label>الاسم</label>
        <input type="text" name="full_name" required>

        <label>رقم التليفون</label>
        <input type="text" name="phone" required>

        <label>الإيميل</label>
        <input type="email" name="email" required>

        <label>السن</label>
        <input type="number" name="age" required>

        <label>المرحلة الدراسية</label>
        <select name="education_stage" id="education_stage" required>
            <option value="">اختر المرحلة</option>
            <option value="اعدادي">إعدادي</option>
            <option value="ثانوي">ثانوي</option>
            <option value="جامعة">جامعة</option>
            <option value="خريجين">خريجين</option>
        </select>

        <label>المدرسة / الكلية</label>
        <input type="text" name="school_or_college">

        <label>العنوان</label>
        <input type="text" name="address" required>

        <label>أب الاعتراف</label>
        <input type="text" name="confession_father" required>

        <label>مشترك في خدمات إيه؟</label>
        <textarea name="services"></textarea>

        <label>هل التحقت بفصل إعداد خدام؟</label>
        <select name="preparation_class" required>
            <option value="1">نعم</option>
            <option value="0">لا</option>
        </select>

        <label>القسم اللي حابب تشترك فيه</label>
        <select name="department" id="department" required>
            <option value="">اختر القسم</option>
        </select>

        <label>ليه حابب تنضم لفريق الصرخة؟</label>
        <textarea name="why_join" required></textarea>

        <button type="submit">إرسال الطلب</button>
    </form>
</div>

<script>
    const stage = document.getElementById('education_stage');
    const dept = document.getElementById('department');

    const map = {
        "اعدادي": ["تمثيل وإخراج", "سينوغرافيا"],
        "ثانوي": ["تمثيل وإخراج", "سينوغرافيا", "تأليف"],
        "جامعة": ["تمثيل وإخراج", "سينوغرافيا", "تأليف"],
        "خريجين": ["تأليف"]
    };

    stage.addEventListener('change', function () {
        dept.innerHTML = '<option value="">اختر القسم</option>';
        if (map[this.value]) {
            map[this.value].forEach(d => {
                const o = document.createElement('option');
                o.value = d;
                o.textContent = d;
                dept.appendChild(o);
            });
        }
    });
</script>

@endsection
