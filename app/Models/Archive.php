<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Archive extends Model
{
    protected $fillable = [
        'title',
        'description',
        'poster_path',
        'video_url',
        'year',
    ];

    // ✅ علاقة الصور المتعددة
    public function images()
    {
        return $this->hasMany(ArchiveImage::class);
    }
}
