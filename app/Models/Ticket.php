<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'booking_id',
        'ticket_code',
        'qr_image_path',
        'is_scanned',
        'scanned_at',
        'scanned_by_admin_id',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
