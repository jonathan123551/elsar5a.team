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
        // لو الميعاد خلص أو مفيش تذاكر متاحة
        abort_if($showTime->is_sold_out || $showTime->available_tickets <= 0, 404);

       $transferWallet = Setting::get('transfer_wallet', '');
$transferInsta  = Setting::get('transfer_insta', '');
        return view('bookings.create', compact('showTime', 'transferWallet', 'transferInsta'));
    }

    public function store(Request $request, ShowTime $showTime)
    {
        // عدد التذاكر ثابت = 1 بغض النظر عن اللي جاي من الفورم
        $ticketsCount = 1;

        $request->validate([
            'full_name'          => 'required|string|max:255',
            'phone'              => 'required|string|max:20',
            // مش محتاجين نثق في قيمة جاية من اليوزر
            // 'tickets_count'   => 'required|integer|min:1|max:10',
            'payment_screenshot' => 'required|image|max:4096',
        ]);

        // تأكد تاني إن في تذاكر كفاية
        if ($showTime->is_sold_out || $showTime->available_tickets < $ticketsCount) {
            return back()
                ->withErrors(['tickets_count' => 'عدد التذاكر غير متاح.'])
                ->withInput();
        }

        // حفظ صورة التحويل في storage/app/public/payments
        $path = $request->file('payment_screenshot')->store('payments', 'public');

        // السعر من الميعاد نفسه
        $ticketPrice = $showTime->ticket_price;
        $totalPrice  = $ticketPrice * $ticketsCount;

        $booking = Booking::create([
            'show_time_id'             => $showTime->id,
            'full_name'                => $request->full_name,
            'phone'                    => $request->phone,
            'tickets_count'            => $ticketsCount,              // دايمًا 1
            'total_price'              => $totalPrice,
            'transfer_screenshot_path' => $path,                      // نفس الاسم اللي الأدمن بيستخدمه
            'status'                   => 'pending',                  // عشان لوحة التحكم
            'reference_code'           => 'SRC-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
        ]);

        return view('bookings.thankyou', compact('booking'));
    }
}
