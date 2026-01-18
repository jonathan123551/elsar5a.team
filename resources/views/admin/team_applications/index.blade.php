@extends('layouts.admin')

@section('content')

<style>
.admin-box{
    max-width:1200px;
    margin:auto;
    color:#fff;
}
.filters{
    display:grid;
    grid-template-columns:1fr 1fr 1fr;
    gap:10px;
    margin-bottom:15px;
}
.filters input, .filters select{
    padding:12px;
    border-radius:10px;
    border:none;
    font-size:15px;
}
.export-btn{
    background:#22c55e;
    padding:12px 20px;
    border-radius:10px;
    color:#000;
    font-weight:bold;
    text-decoration:none;
}
.count{
    margin:10px 0;
    color:#facc15;
    font-weight:bold;
}
table{
    width:100%;
    border-collapse:collapse;
}
th{
    background:#facc15;
    color:#000;
    padding:12px;
}
td{
    padding:12px;
    border-bottom:1px solid rgba(255,255,255,.1);
}
@media(max-width:768px){
    table, thead{display:none;}
    tr{
        display:block;
        background:#111;
        margin-bottom:10px;
        padding:10px;
        border-radius:12px;
    }
    td{
        display:flex;
        justify-content:space-between;
        border:none;
    }
}
</style>

<div class="admin-box">
    <h2>طلبات الانضمام 🎭</h2>

    <form method="GET" class="filters">
        <input name="search" value="{{ request('search') }}" placeholder="بحث بالاسم / التليفون / الإيميل">

        <select name="education_stage">
            <option value="">كل المراحل</option>
            @foreach(['اعدادي','ثانوي','جامعة','خريجين'] as $stage)
                <option value="{{ $stage }}" @selected(request('education_stage')==$stage)>
                    {{ $stage }}
                </option>
            @endforeach
        </select>

        <select name="department">
            <option value="">كل الأقسام</option>
            @foreach(['تمثيل وإخراج','سينوغرافيا','تأليف'] as $dep)
                <option value="{{ $dep }}" @selected(request('department')==$dep)>
                    {{ $dep }}
                </option>
            @endforeach
        </select>

        <button class="export-btn">فلترة</button>
    </form>

    <a href="{{ route('admin.team_applications.export', request()->query()) }}"
       class="export-btn">
       Export Excel
    </a>

    <div class="count">عدد الطلبات: {{ $count }}</div>

    <table>
        <thead>
            <tr>
               
                <th>الاسم</th>
                <th>التليفون</th>
                <th>الإيميل</th>
                <th>المرحلة</th>
                <th>القسم</th>
                <th>التاريخ</th>
            </tr>
        </thead>
        <tbody>
            @foreach($applications as $app)
            <tr>
              
                <td>{{ $app->full_name }}</td>
                <td>{{ $app->phone }}</td>
                <td>{{ $app->email }}</td>
                <td>{{ $app->education_stage }}</td>
                <td>{{ $app->department }}</td>
                <td>{{ $app->created_at->format('Y-m-d') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $applications->links() }}
</div>
@endsection