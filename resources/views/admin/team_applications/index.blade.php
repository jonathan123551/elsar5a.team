@extends('layouts.app')

@section('content')

<style>
    /* ===== Admin Team Applications – Premium Style ===== */

    .admin-wrapper {
        padding: 30px;
        background: linear-gradient(135deg, #0f0f0f, #1b1b1b);
        border-radius: 16px;
        color: #fff;
    }

    .admin-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }

    .admin-header h2 {
        margin: 0;
        font-size: 26px;
        font-weight: 800;
    }

    .export-btn {
        padding: 12px 20px;
        border-radius: 10px;
        background: linear-gradient(135deg, #f5c542, #e0b838);
        color: #000;
        font-weight: bold;
        text-decoration: none;
        transition: 0.3s;
    }

    .export-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.4);
    }

    /* ===== Filters ===== */
    .filters {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr auto;
        gap: 15px;
        margin-bottom: 25px;
    }

    .filters input,
    .filters select {
        padding: 14px;
        border-radius: 10px;
        border: none;
        font-size: 14px;
    }

    .filters button {
        padding: 14px 22px;
        border-radius: 10px;
        border: none;
        background: #ffffff;
        color: #000;
        font-weight: bold;
        cursor: pointer;
    }

    /* ===== Table ===== */
    .table-wrapper {
        overflow-x: auto;
        border-radius: 14px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1200px;
    }

    thead {
        background: #111;
    }

    thead th {
        padding: 14px;
        font-size: 14px;
        border-bottom: 2px solid #333;
        white-space: nowrap;
    }

    tbody tr {
        background: rgba(255,255,255,0.03);
        transition: 0.2s;
    }

    tbody tr:hover {
        background: rgba(255,255,255,0.08);
    }

    td {
        padding: 14px;
        font-size: 14px;
        text-align: center;
        white-space: nowrap;
    }

    .badge {
        padding: 6px 10px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: bold;
    }

    .badge-yes {
        background: #2ecc71;
        color: #000;
    }

    .badge-no {
        background: #7f8c8d;
    }

    .reason {
        max-width: 220px;
        white-space: normal;
        font-size: 13px;
        opacity: 0.9;
    }

    /* ===== Pagination ===== */
    .pagination {
        margin-top: 25px;
        display: flex;
        justify-content: center;
    }

</style>

<div class="admin-wrapper">

    <div class="admin-header">
        <h2>طلبات التقديم لفريق الصرخة 🎭</h2>

        <a href="{{ route('admin.team_applications.export') }}"
           class="export-btn">
            Export Excel
        </a>
    </div>

    {{-- Filters --}}
    <form method="GET" class="filters">
        <input type="text"
               name="search"
               value="{{ request('search') }}"
               placeholder="🔍 بحث بالاسم / التليفون / الإيميل">

        <select name="education_stage">
            <option value="">كل المراحل</option>
            <option value="اعدادي" {{ request('education_stage')=='اعدادي'?'selected':'' }}>إعدادي</option>
            <option value="ثانوي" {{ request('education_stage')=='ثانوي'?'selected':'' }}>ثانوي</option>
            <option value="جامعة" {{ request('education_stage')=='جامعة'?'selected':'' }}>جامعة</option>
            <option value="خريجين" {{ request('education_stage')=='خريجين'?'selected':'' }}>خريجين</option>
        </select>

        <select name="department">
            <option value="">كل الأقسام</option>
            <option value="تمثيل وإخراج" {{ request('department')=='تمثيل وإخراج'?'selected':'' }}>تمثيل وإخراج</option>
            <option value="سينوغرافيا" {{ request('department')=='سينوغرافيا'?'selected':'' }}>سينوغرافيا</option>
            <option value="تأليف" {{ request('department')=='تأليف'?'selected':'' }}>تأليف</option>
        </select>

        <button>فلترة</button>
    </form>

    {{-- Table --}}
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    
                    <th>الاسم</th>
                    <th>التليفون</th>
                    <th>الإيميل</th>
                    <th>السن</th>
                    <th>المرحلة</th>
                    <th>القسم</th>
                    <th>الخدمات</th>
                    <th>إعداد خدام</th>
                    <th>سبب الانضمام</th>
                    <th>التاريخ</th>
                </tr>
            </thead>
            <tbody>
                @forelse($applications as $app)
                    <tr>
                        
                        <td>{{ $app->full_name }}</td>
                        <td>{{ $app->phone }}</td>
                        <td>{{ $app->email }}</td>
                        <td>{{ $app->age }}</td>
                        <td>{{ $app->education_stage }}</td>
                        <td>{{ $app->department }}</td>
                        <td>{{ $app->services ?? '-' }}</td>
                        <td>
                            @if($app->preparation_class)
                                <span class="badge badge-yes">نعم</span>
                            @else
                                <span class="badge badge-no">لا</span>
                            @endif
                        </td>
                        <td class="reason">
                            {{ \Illuminate\Support\Str::limit($app->why_join, 80) }}
                        </td>
                        <td>{{ $app->created_at->format('Y-m-d') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11">لا توجد طلبات</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $applications->links() }}

</div>

@endsection
