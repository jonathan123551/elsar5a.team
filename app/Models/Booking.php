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

    // الدفع
    'payment_method',
    'payment_status',
    'transfer_screenshot_path',
    'paid_at',

    // الحجز
    'status',              // ✅ ده سبب المشكلة
    'reference_code',
    'qr_code_path',        // ✅ ده سبب المشكلة
    'admin_notes',         // ✅ للرفض

    // الإدارة
    'approved_by_admin_id',
    'approved_at',

    // واتساب
    'whatsapp_sent',
    'whatsapp_sent_at',

    'transfer_screenshot_public_id',
    'qr_code_public_id',
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
