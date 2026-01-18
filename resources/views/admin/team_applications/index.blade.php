@extends('layouts.app')

@section('content')

<style>
/* ===============================
   Team Applications – Admin UI
   =============================== */

.page-wrap {
    padding: 20px;
    max-width: 1200px;
    margin: auto;
}

.page-header {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-bottom: 25px;
}

.page-header h2 {
    color: #fff;
    font-size: 26px;
    font-weight: bold;
}

.filters {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 10px;
}

.filters input,
.filters select {
    padding: 12px;
    border-radius: 10px;
    border: none;
    font-size: 15px;
}

.counter {
    color: #f5c542;
    font-size: 16px;
    font-weight: bold;
}

/* ===== Cards ===== */
.cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 15px;
}

.card {
    background: rgba(0,0,0,0.55);
    border-radius: 14px;
    padding: 18px;
    color: #fff;
    box-shadow: 0 8px 20px rgba(0,0,0,0.4);
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.card .name {
    font-size: 20px;
    font-weight: bold;
    color: #f5c542;
}

.card span {
    font-size: 14px;
    opacity: 0.95;
}

.card .badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    background: #f5c542;
    color: #000;
    font-size: 12px;
    font-weight: bold;
    width: fit-content;
}

/* ===== Mobile ===== */
@media (max-width: 600px) {
    .filters {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="page-wrap">

    <div class="page-header">
        <h2>طلبات الانضمام لفريق الصرخة 🎭</h2>

        <div class="filters">
            <input type="text" id="searchInput" placeholder="🔍 بحث بالاسم / التليفون">

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

        <div class="counter">
            عدد الطلبات: <span id="counter">{{ $applications->count() }}</span>
        </div>
    </div>

    <div class="cards" id="cardsWrapper">
        @foreach($applications as $app)
        <div class="card"
             data-search="{{ strtolower($app->full_name.' '.$app->phone) }}"
             data-stage="{{ $app->education_stage }}"
             data-department="{{ $app->department }}">

            <div class="name">{{ $app->full_name }}</div>

            <span>📞 {{ $app->phone }}</span>
            <span>📧 {{ $app->email }}</span>
            <span>🎂 {{ $app->age }} سنة</span>

            <span class="badge">{{ $app->education_stage }}</span>
            <span class="badge">{{ $app->department }}</span>

            <span>👤 أب الاعتراف: {{ $app->confession_father }}</span>
            <span>📅 {{ $app->created_at->format('Y-m-d') }}</span>

        </div>
        @endforeach
    </div>

</div>

<script>
const searchInput = document.getElementById('searchInput');
const stageFilter = document.getElementById('stageFilter');
const deptFilter  = document.getElementById('deptFilter');
const cards = document.querySelectorAll('.card');
const counter = document.getElementById('counter');

function normalize(t) {
    return (t || '').toLowerCase().trim();
}

function filterCards() {
    let visible = 0;

    cards.forEach(card => {
        const search = normalize(card.dataset.search);
        const stage  = normalize(card.dataset.stage);
        const dept   = normalize(card.dataset.department);

        const sVal = normalize(searchInput.value);
        const stVal = normalize(stageFilter.value);
        const dVal = normalize(deptFilter.value);

        const okSearch = search.includes(sVal);
        const okStage  = stVal === '' || stage.includes(stVal);
        const okDept   = dVal === '' || dept.includes(dVal);

        if (okSearch && okStage && okDept) {
            card.style.display = '';
            visible++;
        } else {
            card.style.display = 'none';
        }
    });

    counter.innerText = visible;
}

searchInput.addEventListener('input', filterCards);
stageFilter.addEventListener('change', filterCards);
deptFilter.addEventListener('change', filterCards);
</script>

@endsection