@extends('layouts.app')

@section('content')

<style>
/* ================= BASE ================= */
.page-title {
    font-size: 26px;
    font-weight: bold;
    margin-bottom: 10px;
}

.counter-box {
    margin: 15px 0 20px;
    padding: 12px 18px;
    border-radius: 12px;
    background: linear-gradient(135deg, #1f1f1f, #2a2a2a);
    font-size: 17px;
    font-weight: bold;
    display: inline-block;
}

.counter-box span {
    color: #f5c542;
    font-size: 22px;
}

.export-btn {
    display: inline-block;
    background: #28a745;
    color: #fff;
    padding: 12px 16px;
    border-radius: 10px;
    font-weight: bold;
    margin-bottom: 15px;
    text-decoration: none;
}

/* ================= FILTERS ================= */
.filters {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.filters input,
.filters select {
    padding: 12px 14px;
    border-radius: 10px;
    border: 1px solid #ddd;
    background: #fff;
    color: #000;
    min-width: 200px;
}

/* ================= TABLE ================= */
.table-wrapper {
    background: rgba(0,0,0,0.45);
    border-radius: 16px;
    padding: 20px;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1200px;
}

th {
    background: #111;
    color: #f5c542;
    padding: 14px;
}

td {
    padding: 12px;
    text-align: center;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

/* ================= BADGES ================= */
.badge-yes {
    background: #28a745;
    padding: 5px 10px;
    border-radius: 20px;
    font-weight: bold;
}

.badge-no {
    background: #dc3545;
    padding: 5px 10px;
    border-radius: 20px;
    font-weight: bold;
}

/* ================= MOBILE ================= */
@media (max-width: 768px) {

    .page-title {
        text-align: center;
        font-size: 22px;
    }

    .counter-box {
        display: block;
        text-align: center;
    }

    .filters {
        flex-direction: column;
    }

    .filters input,
    .filters select {
        width: 100%;
    }

    table thead {
        display: none;
    }

    table, tbody, tr, td {
        display: block;
        width: 100%;
    }

    tr {
        background: rgba(0,0,0,0.6);
        margin-bottom: 16px;
        padding: 12px;
        border-radius: 14px;
    }

    td {
        text-align: right;
        padding: 8px 0;
        border: none;
    }

    td::before {
        content: attr(data-label);
        font-weight: bold;
        color: #f5c542;
        float: left;
    }
}
</style>

<div class="container">

    <h2 class="page-title">طلبات التقديم لفريق الصرخة 🎭</h2>

    <div class="counter-box">
        عدد الطلبات: <span id="counter">{{ $applications->count() }}</span>
    </div>

    <br>

    <a href="{{ route('admin.team_applications.export') }}" class="export-btn">
        Export Excel
    </a>

    <div class="filters">
        <input type="text" id="searchInput" placeholder="بحث بالاسم / التليفون / الإيميل">

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
                    <th>إعداد خدام</th>
                    <th>التاريخ</th>
                </tr>
            </thead>
            <tbody>
                @foreach($applications as $app)
                <tr>
                    
                    <td data-label="الاسم">{{ $app->full_name }}</td>
                    <td data-label="التليفون">{{ $app->phone }}</td>
                    <td data-label="الإيميل">{{ $app->email }}</td>
                    <td data-label="السن">{{ $app->age }}</td>
                    <td data-label="المرحلة">{{ $app->education_stage }}</td>
                    <td data-label="القسم">{{ $app->department }}</td>
                    <td data-label="إعداد خدام">
                        @if($app->preparation_class)
                            <span class="badge-yes">نعم</span>
                        @else
                            <span class="badge-no">لا</span>
                        @endif
                    </td>
                    <td data-label="التاريخ">{{ $app->created_at->format('Y-m-d') }}</td>
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
const rows = document.querySelectorAll('#applicationsTable tbody tr');
const counter = document.getElementById('counter');

function filterTable() {
    let visible = 0;

    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        const stage = row.children[5].innerText;
        const dept  = row.children[6].innerText;

        const matchSearch = text.includes(searchInput.value.toLowerCase());
        const matchStage  = stageFilter.value === "" || stage === stageFilter.value;
        const matchDept   = deptFilter.value === "" || dept === deptFilter.value;

        if (matchSearch && matchStage && matchDept) {
            row.style.display = "";
            visible++;
        } else {
            row.style.display = "none";
        }
    });

    counter.innerText = visible;
}

searchInput.addEventListener('input', filterTable);
stageFilter.addEventListener('change', filterTable);
deptFilter.addEventListener('change', filterTable);
</script>

@endsection