@extends('layouts.app')

@section('content')

<style>
/* ===== Page ===== */
.page-wrapper {
    max-width: 1200px;
    margin: auto;
    padding: 20px;
    color: #fff;
}

/* ===== Filter Card ===== */
.filter-card {
    background: rgba(0,0,0,0.65);
    backdrop-filter: blur(8px);
    border-radius: 18px;
    padding: 18px;
    margin-bottom: 25px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.35);
}

.filter-card h2 {
    margin-bottom: 16px;
    color: #f5c542;
    font-weight: bold;
}

/* Inputs */
.filter-card input,
.filter-card select {
    width: 100%;
    padding: 14px 16px;
    border-radius: 12px;
    border: none;
    font-size: 16px;
    background: #fff;
    color: #000;
}

.filter-card input::placeholder {
    color: #777;
}

/* Grid */
.filter-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 12px;
}

/* Counter */
.counter {
    margin-top: 14px;
    font-size: 16px;
    font-weight: bold;
    color: #f5c542;
}

/* Export */
.export-btn {
    display: inline-block;
    margin-top: 14px;
    background: #2ecc71;
    color: #000;
    padding: 14px 20px;
    border-radius: 12px;
    font-size: 16px;
    font-weight: bold;
    text-decoration: none;
}

/* ===== Table ===== */
.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: rgba(0,0,0,0.55);
    border-radius: 16px;
    overflow: hidden;
}

thead {
    background: #f5c542;
    color: #000;
}

th, td {
    padding: 14px;
    text-align: center;
    border-bottom: 1px solid rgba(255,255,255,0.15);
    white-space: nowrap;
}

tbody tr:hover {
    background: rgba(255,255,255,0.08);
}

/* ===== Desktop ===== */
@media (min-width: 768px) {
    .filter-grid {
        grid-template-columns: 2fr 1fr 1fr;
    }
}
</style>

<div class="page-wrapper">

    <div class="filter-card">
        <h2>🎭 طلبات الانضمام لفريق الصرخة</h2>

        <div class="filter-grid">
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
        </div>

        <a href="{{ route('admin.team_applications.export') }}" class="export-btn">
            ⬇️ Export Excel
        </a>

        <div class="counter">
            عدد الطلبات: <span id="counter">{{ $applications->count() }}</span>
        </div>
    </div>

    <div class="table-wrapper">
        <table id="applicationsTable">
            <thead>
                <tr>
                    
                    <th>الاسم</th>
                    <th>التليفون</th>
                    <th>الإيميل</th>
                    <th>السن</th>
                    <th>المرحلة</th>
                    <th>القسم</th>
                    <th>أب الاعتراف</th>
                    <th>تاريخ التقديم</th>
                </tr>
            </thead>
            <tbody>
                @foreach($applications as $app)
                <tr>
                 
                    <td>{{ $app->full_name }}</td>
                    <td>{{ $app->phone }}</td>
                    <td>{{ $app->email }}</td>
                    <td>{{ $app->age }}</td>
                    <td>{{ $app->education_stage }}</td>
                    <td>{{ $app->department }}</td>
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
const tableRows   = document.querySelectorAll('#applicationsTable tbody tr');
const counter     = document.getElementById('counter');

function filterTable() {
    let count = 0;
    const search = searchInput.value.toLowerCase();
    const stage  = stageFilter.value;
    const dept   = deptFilter.value;

    tableRows.forEach(row => {
        const text = row.innerText.toLowerCase();
        const rowStage = row.children[5].innerText;
        const rowDept  = row.children[6].innerText;

        let visible = true;

        if (search && !text.includes(search)) visible = false;
        if (stage && rowStage !== stage) visible = false;
        if (dept && !rowDept.includes(dept)) visible = false;

        row.style.display = visible ? '' : 'none';
        if (visible) count++;
    });

    counter.innerText = count;
}

searchInput.addEventListener('input', filterTable);
stageFilter.addEventListener('change', filterTable);
deptFilter.addEventListener('change', filterTable);
</script>

@endsection