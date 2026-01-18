@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h2 class="mb-4">طلبات التقديم لفريق الصرخة 🎭</h2>

    <a href="{{ route('admin.team_applications.export') }}"
       class="btn btn-success mb-3">
        Export Excel
    </a>

    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>الاسم</th>
                    <th>التليفون</th>
                    <th>الإيميل</th>
                    <th>السن</th>
                    <th>المرحلة</th>
                    <th>المدرسة / الكلية</th>
                    <th>العنوان</th>
                    <th>أب الاعتراف</th>
                    <th>الخدمات</th>
                    <th>إعداد خدام</th>
                    <th>القسم</th>
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
                    <td>{{ $app->school_or_college ?? '-' }}</td>
                    <td>{{ $app->address }}</td>
                    <td>{{ $app->confession_father }}</td>
                    <td>{{ $app->services ?? '-' }}</td>
                    <td>
                        {{ $app->preparation_class ? 'نعم' : 'لا' }}
                    </td>
                    <td>{{ $app->department }}</td>
                    <td style="max-width: 250px">
                        {{ $app->why_join }}
                    </td>
                    <td>{{ $app->created_at->format('Y-m-d H:i') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="14" class="text-center text-muted">
                        لا توجد طلبات حتى الآن
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $applications->links() }}
</div>
@endsection
