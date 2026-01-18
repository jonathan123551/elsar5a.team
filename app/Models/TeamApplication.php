<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamApplication extends Model
{
    protected $fillable = [
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
    ];
}