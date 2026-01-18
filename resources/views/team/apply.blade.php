@extends('layouts.app')

@section('content')

<style>
    /* ===== Team Application – Professional Style ===== */

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
        transition: all 0.25s ease;
    }

    .team-form input::placeholder,
    .team-form textarea::placeholder {
        color: #666;
    }

    .team-form input:focus,
    .team-form textarea:focus,
    .team-form select:focus {
        outline: none;
        border-color: #f5c542;
        box-shadow: 0 0 0 3px rgba(245,197,66,0.3);
        background-color: #fff;
    }

    .team-form textarea {
        resize: vertical;
    }

    .team-form button {
        margin-top: 10px;
        width: 100%;
        padding: 15px;
        font-size: 18px;
        font-weight: bold;
        border-radius: 12px;
        border: none;
        background: linear-gradient(135deg, #f5c542, #e0b838);
        color: #000;
        cursor: pointer;
        transition: 0.3s;
    }

    .team-form button:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.3);
    }
</style>

<div class="team-form">
    <h2>التقديم لفريق الصرخة المسرحي 🎭</h2>

    
    @if(session('success'))
    <div id="success-box" style="
        background: rgba(0, 128, 0, 0.15);
        border: 1px solid #3cff00;
        color: #ffffff;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
        text-align: center;
        font-size: 18px;
        font-weight: bold;
    ">
        {{ session('success') }}
        <br>
        <small>سيتم تحويلك خلال ثواني...</small>
    </div>

    <script>
        setTimeout(() => {
            window.location.href = "https://chat.whatsapp.com/LRklhOxHEPu3M1UtxsdOvX";
        }, 3000); // 3 ثواني
    </script>
@endif

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
        <select name="education_level" id="education_level" required>
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
        <select name="department" id="department" required>
            <option value="">اختر القسم</option>
        </select>

        <label>ليه حابب تنضم لفريق الصرخة؟</label>
        <textarea name="reason" rows="4" placeholder="اكتب سبب انضمامك"></textarea>

        <button type="submit">إرسال الطلب</button>
    </form>
</div>

<script>
    const education = document.getElementById('education_level');
    const department = document.getElementById('department');

    const options = {
        "اعدادي": [
            "تمثيل وإخراج",
            "سينوغرافيا"
        ],
        "ثانوي": [
            "تمثيل وإخراج",
            "سينوغرافيا",
            "تأليف"
        ],
        "جامعة": [
            "تمثيل وإخراج",
            "سينوغرافيا",
            "تأليف"
        ],
        "خريجين": [
            "تأليف"
        ]
    };

    education.addEventListener('change', function () {
        department.innerHTML = '<option value="">اختر القسم</option>';

        if (options[this.value]) {
            options[this.value].forEach(dep => {
                const option = document.createElement('option');
                option.value = dep;
                option.textContent = dep;
                department.appendChild(option);
            });
        }
    });
</script>

@endsection
