@extends('layouts.app')

@section('content')

<style>
/* ===============================
   Admin Table – Team Applications
   =============================== */

.page-wrapper {
    padding: 25px;
    max-width: 1300px;
    margin: auto;
}

.page-title {
    color: #fff;
    font-size: 28px;
    font-weight: bold;
    margin-bottom: 20px;
}

/* ===== Controls ===== */
.controls {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 15px;
}

.controls input,
.controls select {
    padding: 10px 14px;
    border-radius: 8px;
    border: none;
    font-size: 15px;
    min-width: 200px;
}

.controls input::placeholder {
    color: #333;
    font-weight: 500;
}

.counter {
    color: #f5c542;
    font-weight: bold;
    margin-top: 8px;
}

/* ===== Table ===== */
.table-wrap {
    overflow-x: auto;
    background: rgba(0,0,0,0.55);
    border-radius: 12px;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1000px;
}

thead {
    background: #f5c542;
    color: #000;
}

thead th {
    padding: 14px;
    font-weight: bold;
    text-align: right;
    font-size: 14px;
}

tbody td {
    padding: 12px;
    color: #fff;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    font-size: 14px;
}

tbody tr:hover {
    background: rgba(255,255,255,0.05);
}

.badge {
    padding: 4px 10px;
    border-radius: 20px;
    background: #f5c542;
    color: #000;
    font-size: 12px;
    font-weight: bold;
}

/* ===== Export ===== */
.export-btn {
    background: #2ecc71;
    color: #000;
    padding: 10px 16px;
    border-radius: 8px;
    font-weight: bold;
    text-decoration: none;
}

/* ===== Mobile ===== */
@media (max-width: 600px) {
    .page-title {
        font-size: 22px;
    }
}
</style>

<div class="page-wrapper">

    <div class="page-title">طلبات الانضمام لفريق الصرخة 🎭</div>

    <div class="controls">
        <input type="text" id="searchInput" placeholder="بحث بالاسم أو التليفون">

        <select id="stageFilter">
            <option value="">كل المراحل</option>
            <option value="اعدادي">إعدادي</option>
            <option value="ثانوي">ثانوي</option>
            <option value="جامعة">جامعة</option>
            <option value="خريجين">خريجين</option>
        </select>

        <select id="deptFilter">
            <option value="">كل الأقسام</option>
            <option value="تمثيل">تمثيل وإخراج</option>
            <option value="سينوغرافيا">سينوغرافيا</option>
            <option value="تأليف">تأليف</option>
        </select>

        <a href="{{ route('admin.team_applications.export') }}" class="export-btn">
            Export Excel
        </a>
    </div>

    <div class="counter">
        عدد الطلبات: <span id="counter">{{ $applications->count() }}</span>
    </div>

    <div class="table-wrap mt-3">
        <table>
            <thead>
                <tr>
                    
                    <th>الاسم</th>
                    <th>التليفون</th>
                    <th>الإيميل</th>
                    <th>السن</th>
                    <th>المرحلة</th>
                    <th>القسم</th>
                    <th>أب الاعتراف</th>
                    <th>التاريخ</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                @foreach($applications as $app)
                <tr
                    data-search="{{ strtolower($app->full_name.' '.$app->phone) }}"
                    data-stage="{{ $app->education_stage }}"
                    data-dept="{{ $app->department }}"
                >
                   
                    <td>{{ $app->full_name }}</td>
                    <td>{{ $app->phone }}</td>
                    <td>{{ $app->email }}</td>
                    <td>{{ $app->age }}</td>
                    <td><span class="badge">{{ $app->education_stage }}</span></td>
                    <td><span class="badge">{{ $app->department }}</span></td>
                    <td>{{ $app->confession_father }}</td>
                    <td>{{ $app->created_at->format('Y-m-d') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>

<script>
const searchInput = document.getElementById('searchInput');
const stageFilter = document.getElementById('stageFilter');
const deptFilter  = document.getElementById('deptFilter');
const rows = document.querySelectorAll('#tableBody tr');
const counter = document.getElementById('counter');

function normalize(v) {
    return (v || '').toLowerCase().trim();
}

function applyFilters() {
    let visible = 0;

    rows.forEach(row => {
        const search = normalize(row.dataset.search);
        const stage  = normalize(row.dataset.stage);
        const dept   = normalize(row.dataset.dept);

        const sVal = normalize(searchInput.value);
        const stVal = normalize(stageFilter.value);
        const dVal = normalize(deptFilter.value);

        const okSearch = search.includes(sVal);
        const okStage  = stVal === '' || stage.includes(stVal);
        const okDept   = dVal === '' || dept.includes(dVal);

        if (okSearch && okStage && okDept) {
            row.style.display = '';
            visible++;
        } else {
            row.style.display = 'none';
        }
    });

    counter.innerText = visible;
}

searchInput.addEventListener('input', applyFilters);
stageFilter.addEventListener('change', applyFilters);
deptFilter.addEventListener('change', applyFilters);
</script>

@endsection