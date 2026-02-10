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
        abort_if(
            $showTime->is_sold_out || $showTime->available_tickets <= 0,
            404
        );

        $transferWallet = Setting::get('transfer_wallet', '');
        $transferInsta  = Setting::get('transfer_insta', '');

        return view('bookings.create', compact(
            'showTime',
            'transferWallet',
            'transferInsta'
        ));
    }

    public function store(Request $request, ShowTime $showTime)
    {
        // 🎟️ عدد التذاكر ثابت
        $ticketsCount = 1;

        // ✅ Validation (URL مش File)
        $request->validate([
            'full_name'               => 'required|string|max:255',
            'phone'                   => 'required|string|min:8|max:20',
            'payment_screenshot'  => 'required|url',
        ]);

        if ($showTime->is_sold_out || $showTime->available_tickets < $ticketsCount) {
            return back()
                ->withErrors(['tickets_count' => 'عدد التذاكر غير متاح.'])
                ->withInput();
        }

        /*
        |------------------------------------------------------------------
        | 📞 Normalize phone number (Egypt → WhatsApp E.164)
        |------------------------------------------------------------------
        */
        try {
            $phone = $this->normalizeEgyptPhone($request->phone);
        } catch (\Exception $e) {
            return back()
                ->withErrors(['phone' => $e->getMessage()])
                ->withInput();
        }

        // 💰 السعر
        $ticketPrice = $showTime->ticket_price;
        $totalPrice  = $ticketPrice * $ticketsCount;

        /*
        |------------------------------------------------------------------
        | 🧾 Create booking (URL only)
        |------------------------------------------------------------------
        */
        $booking = Booking::create([
            'show_time_id'             => $showTime->id,
            'full_name'                => $request->full_name,
            'phone'                    => $phone,
            'tickets_count'            => $ticketsCount,
            'total_price'              => $totalPrice,

            // ☁️ Cloudinary URL من الفرونت
            'transfer_screenshot_path' => $request->payment_screenshot_url,

            'status'                   => 'pending',
            'reference_code'           => 'SRC-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
        ]);

        return view('bookings.thankyou', compact('booking'));
    }

    /*
    |------------------------------------------------------------------
    | 🔧 Helpers
    |------------------------------------------------------------------
    */
    private function normalizeEgyptPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (preg_match('/^01[0-9]{9}$/', $phone)) {
            return '20' . substr($phone, 1);
        }

        if (preg_match('/^1[0-9]{9}$/', $phone)) {
            return '20' . $phone;
        }

        if (preg_match('/^20[0-9]{10}$/', $phone)) {
            return $phone;
        }

        throw new \Exception('رقم الموبايل غير صالح، من فضلك تأكد من كتابته بشكل صحيح');
    }
}
