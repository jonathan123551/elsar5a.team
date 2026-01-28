@extends('layouts.app')

@section('content')

<!-- 🔒 CLOSED OVERLAY -->
<div id="closedOverlay" style="
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.88);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    color: #fff;
">

    <div style="
        background: #111;
        padding: 45px 35px;
        border-radius: 22px;
        max-width: 420px;
        width: 90%;
        box-shadow: 0 0 35px rgba(245,197,66,0.6);
    ">
        <h2 style="
            color:#f5c542;
            margin-bottom:18px;
            font-size: 26px;
        ">
            🚫 تم غلق باب التقديم
        </h2>

        <p style="
            font-size:16px;
            line-height:1.8;
            margin-bottom: 30px;
        ">
            نشكركم على اهتمامكم بالانضمام إلى فريق الصرخة المسرحي 🎭<br>
        </p>

        <a href="{{ url('/') }}" style="
            display: inline-block;
            background: linear-gradient(135deg, #f5c542, #e0b838);
            color: #000;
            padding: 14px 28px;
            border-radius: 14px;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
        ">
            ⬅️ الرجوع للصفحة الرئيسية
        </a>
    </div>
</div>

<!-- ❌ منع أي Scroll أو تفاعل -->
<style>
body {
    overflow: hidden;
}
</style>

@endsection
