<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShowTime extends Model
{
    use HasFactory;

    protected $fillable = [
        'show_id',
        'date',
        'time',
        'ticket_price',
        'total_tickets',
        'available_tickets',
        'is_sold_out',
    ];

    protected $casts = [
        'date' => 'date',
        'is_sold_out' => 'boolean',
    ];

    // العلاقة مع العرض نفسه
    public function show()
    {
        return $this->belongsTo(Show::class);
    }

    // 👈 دي اللي ناقصاك: كل الحجوزات المرتبطة بالميعاد ده
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'show_time_id');
    }
}
