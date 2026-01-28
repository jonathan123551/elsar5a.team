@extends('layouts.app')

@section('content')
<style>
.page-wrapper {
    max-width: 1200px;
    margin: auto;
    padding: 20px;
    color: #fff;
}

.filter-card {
    background: rgba(0,0,0,0.65);
    backdrop-filter: blur(8px);
    border-radius: 18px;
    padding: 18px;
    margin-bottom: 25px;
}

.filter-card h2 {
    color: #f5c542;
    margin-bottom: 15px;
}

.filter-grid {
    display: grid;
    gap: 12px;
}

.filter-card input,
.filter-card select {
    padding: 14px;
    border-radius: 12px;
    border: none;
    font-size: 15px;
    background: #fff;
    color: #000;
}

.export-btn {
    display: inline-block;
    margin-top: 15px;
    background: #2ecc71;
    color: #000;
    padding: 12px 18px;
    border-radius: 12px;
    font-weight: bold;
    text-decoration: none;
}

.counter {
    margin-top: 10px;
    color: #f5c542;
    font-weight: bold;
}

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: rgba(0,0,0,0.55);
    border-radius: 16px;
}

thead {
    background: #f5c542;
    color: #000;
}

th, td {
    padding: 14px;
    text-align: center;
    white-space: nowrap;
}

tbody tr:hover {
    background: rgba(255,255,255,0.1);
}

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
            <input type="text" id="searchInput" placeholder="بحث بالاسم أو التليفون أو الإيميل">

            <select id="stageFilter">
                <option value="">كل المراحل</option>
                <option value="اعدادي">إعدادي</option>
                <option value="ثانوي">ثانوي</option>
                <option value="جامعة">جامعة</option>
                <option value="خريجين">خريجين</option>
            </select>

            <select id="deptFilter">
                <option value="">كل الأقسام</option>
                <option value="تمثيل وإخراج">تمثيل وإخراج</option>
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
const rows        = document.querySelectorAll('#applicationsTable tbody tr');
const counter     = document.getElementById('counter');

function filterTable() {
    let count = 0;
    const search = searchInput.value.toLowerCase();
    const stage  = stageFilter.value;
    const dept   = deptFilter.value;

    rows.forEach(row => {
        const name  = row.children[0].innerText.toLowerCase();
        const phone = row.children[1].innerText.toLowerCase();
        const email = row.children[2].innerText.toLowerCase();
        const rowStage = row.children[4].innerText;
        const rowDept  = row.children[5].innerText;

        let visible = true;

        if (search && !name.includes(search) && !phone.includes(search) && !email.includes(search))
            visible = false;

        if (stage && rowStage !== stage)
            visible = false;

        if (dept && rowDept !== dept)
            visible = false;

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