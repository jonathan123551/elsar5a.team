<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Show extends Model
{
   protected $fillable = [
    'title',
    'description',
    'poster_path',
    'is_active',
    'ticket_template_path',
    'ticket_qr_x',
    'ticket_qr_y',
    'ticket_qr_size',
    'poster_public_id',
    'ticket_template_public_id',
];


    public function showTimes()
    {
        return $this->hasMany(ShowTime::class);
    }
}
