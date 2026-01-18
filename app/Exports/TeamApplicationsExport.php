<?php

namespace App\Exports;

use App\Models\TeamApplication;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TeamApplicationsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return TeamApplication::select(
            'full_name',
            'phone',
            'email',
            'age',
            'education_stage',
            'school_or_college',
            'address',
            'confession_father',
            'services',
            'preparation_class',
            'department',
            'why_join',
            'created_at'
        )->get();
    }

    public function headings(): array
    {
        return [
            'الاسم',
            'التليفون',
            'الإيميل',
            'السن',
            'المرحلة',
            'المدرسة / الكلية',
            'العنوان',
            'أب الاعتراف',
            'الخدمات',
            'إعداد خدام',
            'القسم',
            'سبب الانضمام',
            'تاريخ التقديم',
        ];
    }
}
