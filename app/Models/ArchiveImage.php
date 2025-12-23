<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArchiveImage extends Model
{
    protected $fillable = [
        'archive_id',
        'image_path',
        'image_public_id',
    ];

    public function archive()
    {
        return $this->belongsTo(Archive::class);
    }
}
