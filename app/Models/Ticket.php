<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'booking_id',
        'name',
        'phone',
        'ticket_code',
        'qr_image_path',
        'is_scanned',
        'scanned_at',
        'scanned_by_admin_id',
        'whatsapp_sent',
    ];

    public function booking()
    {
        return $this->belongsTo(\App\Models\Booking::class);
    }
}