<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Archive extends Model
{
    protected $fillable = [
        'title',        // اسم العرض
        'description',  // وصف العرض
        'images',       // صور متعددة
        'poster_path',
        'video_url',    // لينك فيديو
        'year',         // سنة العرض
    ];

    public function images()
    {
        return $this->hasMany(ArchiveImage::class);
    }
}
