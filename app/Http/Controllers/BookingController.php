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
            'url' => ['secure' => true],
        ]);
    }

    public function create(ShowTime $showTime)
    {
        abort_if(
            $showTime->is_sold_out || $showTime->available_tickets <= 0,
            404
        );

        return view('bookings.create', [
            'showTime'       => $showTime,
            'transferWallet' => Setting::get('transfer_wallet', ''),
            'transferInsta'  => Setting::get('transfer_insta', ''),
        ]);
    }

   public function store(Request $request, ShowTime $showTime)
{
    $lockKey = 'booking_lock_' . sha1(
        $request->ip() . json_encode($request->phones ?? []) . $showTime->id
    );

    if (!Cache::add($lockKey, true, 20)) {
        return back()->withErrors([
            'general' => '⏳ الطلب قيد المعالجة بالفعل، من فضلك انتظر.'
        ])->withInput();
    }

    try {

        // ✅ Validation
        $request->validate([
            'names'  => ['required', 'array'],
            'names.*' => ['required', 'string', 'max:255'],

            'phones'  => ['required', 'array'],
            'phones.*' => ['required', 'string', 'min:8', 'max:20'],

            'payment_screenshot' => 'required|image|max:16000',
        ]);

        if ($showTime->is_sold_out || $showTime->available_tickets < 1) {
            return back()->withErrors([
                'general' => 'عدد التذاكر غير متاح'
            ])->withInput();
        }

        $ticketsCount = count($request->names);

        if ($showTime->available_tickets < $ticketsCount) {
            return back()->withErrors([
                'general' => 'عدد التذاكر المطلوب أكبر من المتاح'
            ])->withInput();
        }

        // 📞 أول رقم
        $mainPhone = $this->normalizeEgyptPhone($request->phones[0]);

        // ☁️ Upload screenshot
        $file = $request->file('payment_screenshot');
        $tempPath = sys_get_temp_dir() . '/' . uniqid('payment_', true);
        file_put_contents($tempPath, file_get_contents($file->getRealPath()));

        $upload = (new UploadApi())->upload(
            $tempPath,
            ['folder' => 'payments/screenshots']
        );

        @unlink($tempPath);

        // ✅ Create booking
        $booking = Booking::create([
            'show_time_id'                  => $showTime->id,
            'full_name'                     => $request->names[0],
            'phone'                         => $mainPhone,
            'tickets_count'                 => $ticketsCount,
            'total_price'                   => $showTime->ticket_price * $ticketsCount,
            'transfer_screenshot_path'      => $upload['secure_url'],
            'transfer_screenshot_public_id' => $upload['public_id'],
            'status'                        => 'pending',
            'reference_code'                =>
                'SRC-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
        ]);

        // 💀 إنشاء التذاكر لكل شخص
        foreach ($request->names as $index => $name) {

            \App\Models\Ticket::create([
                'booking_id' => $booking->id,

                'name'  => $name,
                'phone' => $this->normalizeEgyptPhone($request->phones[$index]),

                'ticket_code' => 'TIC-' . strtoupper(Str::random(6)),
            ]);
        }

        return view('bookings.thankyou', compact('booking'));

    } finally {
        Cache::forget($lockKey);
    }
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