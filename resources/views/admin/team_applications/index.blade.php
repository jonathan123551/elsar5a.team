@extends('layouts.app')

@section('content')

<div class="card shadow-sm">
    <div class="card-body">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4>طلبات التقديم لفريق الصرخة 🎭</h4>

            <a href="{{ route('admin.team_applications.export') }}"
               class="btn btn-success">
                Export Excel
            </a>
        </div>

        {{-- 🔍 Filters --}}
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-4">
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       class="form-control"
                       placeholder="🔍 بحث بالاسم / التليفون / الإيميل">
            </div>

            <div class="col-md-3">
                <select name="education_stage" class="form-select">
                    <option value="">كل المراحل</option>
                    <option value="اعدادي" {{ request('education_stage')=='اعدادي'?'selected':'' }}>إعدادي</option>
                    <option value="ثانوي" {{ request('education_stage')=='ثانوي'?'selected':'' }}>ثانوي</option>
                    <option value="جامعة" {{ request('education_stage')=='جامعة'?'selected':'' }}>جامعة</option>
                    <option value="خريجين" {{ request('education_stage')=='خريجين'?'selected':'' }}>خريجين</option>
                </select>
            </div>

            <div class="col-md-3">
                <select name="department" class="form-select">
                    <option value="">كل الأقسام</option>
                    <option value="تمثيل وإخراج" {{ request('department')=='تمثيل وإخراج'?'selected':'' }}>تمثيل وإخراج</option>
                    <option value="سينوغرافيا" {{ request('department')=='سينوغرافيا'?'selected':'' }}>سينوغرافيا</option>
                    <option value="تأليف" {{ request('department')=='تأليف'?'selected':'' }}>تأليف</option>
                </select>
            </div>

            <div class="col-md-2 d-grid">
                <button class="btn btn-dark">فلترة</button>
            </div>
        </form>

        {{-- 📋 Table --}}
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle text-center">
                <thead class="table-dark">
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
                        <th>تاريخ التقديم</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($applications as $app)
                        <tr>
                            <td>{{ $app->id }}</td>
                            <td>{{ $app->full_name }}</td>
                            <td>{{ $app->phone }}</td>
                            <td>{{ $app->email }}</td>
                            <td>{{ $app->age }}</td>
                            <td>{{ $app->education_stage }}</td>
                            <td>{{ $app->department }}</td>
                            <td>{{ $app->services ?? '-' }}</td>
                            <td>
                                @if($app->preparation_class)
                                    <span class="badge bg-success">نعم</span>
                                @else
                                    <span class="badge bg-secondary">لا</span>
                                @endif
                            </td>
                            <td style="max-width:200px">
                                {{ \Illuminate\Support\Str::limit($app->why_join, 50) }}
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
</div>

@endsection
