<?php

namespace App\Http\Controllers;

use App\Models\ShowTime;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Setting;

class BookingController extends Controller
{
    public function create(ShowTime $showTime)
    {
        abort_if($showTime->is_sold_out || $showTime->available_tickets <= 0, 404);

        return view('bookings.create', [
            'showTime'       => $showTime,
            'transferWallet'=> Setting::get('transfer_wallet',''),
            'transferInsta' => Setting::get('transfer_insta',''),
        ]);
    }

    public function store(Request $request, ShowTime $showTime)
    {
        $request->validate([
            'full_name'          => 'required|string|max:255',
            'phone'              => 'required|string|min:8|max:20',
            'payment_screenshot' => 'required|url',
        ]);

        $phone = $this->normalizeEgyptPhone($request->phone);

        $booking = Booking::create([
            'show_time_id'             => $showTime->id,
            'full_name'                => $request->full_name,
            'phone'                    => $phone,
            'tickets_count'            => 1,
            'total_price'              => $showTime->ticket_price,
            'transfer_screenshot_path' => $request->payment_screenshot,
            'status'                   => 'pending',
            'reference_code'           => 'SRC-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
        ]);

        return view('bookings.thankyou', compact('booking'));
    }

    private function normalizeEgyptPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (preg_match('/^01[0-9]{9}$/', $phone)) return '20' . substr($phone,1);
        if (preg_match('/^1[0-9]{9}$/', $phone))  return '20' . $phone;
        if (preg_match('/^20[0-9]{10}$/', $phone)) return $phone;

        throw new \Exception('رقم الموبايل غير صالح');
    }
}
