<?php

namespace App\Http\Controllers;

use App\Models\ShowTime;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;

class BookingController extends Controller
{
    public function __construct()
    {
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
        /* ======================================================
        | 🛑 ANTI DOUBLE REQUEST (THE REAL FIX)
        ====================================================== */
        $requestKey = 'booking_lock_' . sha1(
            $request->ip() .
            $request->full_name .
            $request->phone .
            $showTime->id
        );

        if (Cache::has($requestKey)) {
            return back()->withErrors([
                'general' => '⏳ الطلب قيد المعالجة بالفعل، من فضلك انتظر.'
            ]);
        }

        Cache::put($requestKey, true, 30); // lock 30 sec

        try {
            // 🎟️ عدد التذاكر ثابت
            $ticketsCount = 1;

            $request->validate([
                'full_name'          => 'required|string|max:255',
                'phone'              => 'required|string|min:8|max:20',
                'payment_screenshot' => 'required|image|max:16000',
            ]);

            if ($showTime->is_sold_out || $showTime->available_tickets < $ticketsCount) {
                throw new \Exception('عدد التذاكر غير متاح');
            }

            // 📞 Normalize phone
            $phone = $this->normalizeEgyptPhone($request->phone);


            // ☁️ Upload to Cloudinary (safe)
            $file = $request->file('payment_screenshot');
            $tempPath = sys_get_temp_dir() . '/' . uniqid('payment_', true);
            file_put_contents($tempPath, file_get_contents($file->getRealPath()));

            $upload = (new UploadApi())->upload(
                $tempPath,
                ['folder' => 'payments/screenshots']
            );

            @unlink($tempPath);

            // 💰 السعر
            $totalPrice = $showTime->ticket_price * $ticketsCount;

            $booking = Booking::create([
                'show_time_id'                  => $showTime->id,
                'full_name'                     => $request->full_name,
                'phone'                         => $phone,
                'tickets_count'                 => $ticketsCount,
                'total_price'                   => $totalPrice,
                'transfer_screenshot_path'      => $upload['secure_url'],
                'transfer_screenshot_public_id' => $upload['public_id'],
                'status'                        => 'pending',
                'reference_code'                => 'SRC-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
            ]);

            return view('bookings.thankyou', compact('booking'));

        }Cache::put($requestKey, true, 30); // lock 30 sec

    }

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

        throw \Illuminate\Validation\ValidationException::withMessages([
    'phone' => 'رقم الموبايل غير صحيح، من فضلك اكتبه بصيغة صحيحة (مثال: 010xxxxxxxx)',
]);
    }
}
