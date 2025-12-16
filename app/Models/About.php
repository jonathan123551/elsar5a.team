<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class About extends Model
{
    // 👈 مهم: نقوله اسم الجدول يدويًا
    protected $table = 'abouts';

    protected $fillable = [
        'description',
        'youtube',
        'instagram',
        'facebook',
        'founded_year',
    ];
}
