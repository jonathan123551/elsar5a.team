<?php

namespace App\Http\Controllers;

use App\Models\ShowTime;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Setting;
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;

class BookingController extends Controller
{
    public function __construct()
    {
        // 🔥 Cloudinary config (مرة واحدة)
        Configuration::instance([
            'cloud' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                'api_key'    => env('CLOUDINARY_API_KEY'),
                'api_secret' => env('CLOUDINARY_API_SECRET'),
            ],
            'url' => [
                'secure' => true,
            ],
        ]);
    }

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
        // 🎟️ عدد التذاكر ثابت = 1
        $ticketsCount = 1;

        $request->validate([
            'full_name'          => 'required|string|max:255',
            'phone'              => 'required|string|max:20',
            'payment_screenshot' => 'required|image|max:4096',
        ]);

        if ($showTime->is_sold_out || $showTime->available_tickets < $ticketsCount) {
            return back()
                ->withErrors(['tickets_count' => 'عدد التذاكر غير متاح.'])
                ->withInput();
        }

        // 💳 رفع Screenshot على Cloudinary
        $upload = (new UploadApi())->upload(
            $request->file('payment_screenshot')->getRealPath(),
            [
                'folder' => 'payments/screenshots',
            ]
        );

        // 🔥 مهم جدًا
        $screenshotUrl      = $upload['secure_url'];
        $screenshotPublicId = $upload['public_id'];

        // 💰 السعر
        $ticketPrice = $showTime->ticket_price;
        $totalPrice  = $ticketPrice * $ticketsCount;

        $booking = Booking::create([
            'show_time_id'                  => $showTime->id,
            'full_name'                     => $request->full_name,
            'phone'                         => $request->phone,
            'tickets_count'                 => $ticketsCount,
            'total_price'                   => $totalPrice,

            // ✅ Cloudinary
            'transfer_screenshot_path'      => $screenshotUrl,
            'transfer_screenshot_public_id' => $screenshotPublicId,

            'status'                        => 'pending',
            'reference_code'                => 'SRC-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
        ]);

        return view('bookings.thankyou', compact('booking'));
    }
}
