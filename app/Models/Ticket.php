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
        'scanned_by_ip',
        'scanned_by_ua',
        'whatsapp_sent',
        // Concurrency-control state for the WhatsApp send pipeline:
        // pending | sending | sent | failed. See the 2026_05_30
        // add_delivery_status migration + SendWhatsAppTicketImageJob.
        'delivery_status',
    ];

    public function booking()
    {
        return $this->belongsTo(\App\Models\Booking::class);
    }
}
