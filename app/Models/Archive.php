<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Archive extends Model
{
    protected $fillable = [
        'title',
        'description',
        'video_url',
        'year',
        'poster_path',
    ];

    public function images()
    {
        return $this->hasMany(ArchiveImage::class);
    }
}
