<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'show_time_id',
        'full_name',
        'phone',
        'tickets_count',
        'total_price',
        'payment_method',
        'payment_status',
        'transfer_screenshot_path',
        'reference_code',
        'paid_at',
        'approved_by_admin_id',
        'approved_at',
        'whatsapp_sent',
        'whatsapp_sent_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'approved_at' => 'datetime',
        'whatsapp_sent_at' => 'datetime',
    ];

    public function showTime()
    {
        return $this->belongsTo(ShowTime::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
