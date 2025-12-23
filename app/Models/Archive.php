<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Archive extends Model
{
    protected $fillable = [
        'title',
        'description',
        'video_url',
        'facebook_reel',
        'year',
        'poster_path',
        'poster_public_id',
    ];

    public function images()
    {
        return $this->hasMany(ArchiveImage::class);
    }
}
