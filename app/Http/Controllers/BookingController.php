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

    // ================= CREATE =================
    public function create(ShowTime $showTime)
    {
        $reserved = $showTime->bookings()
            ->whereIn('status', ['approved','pending'])
            ->sum('tickets_count');

        $remaining = max(0, $showTime->total_tickets - $reserved);

        abort_if($remaining <= 0 || $showTime->is_sold_out, 404);

        return view('bookings.create', [
            'showTime'       => $showTime,
            'transferWallet' => Setting::get('transfer_wallet', ''),
            'transferInsta'  => Setting::get('transfer_insta', ''),
            'remaining'      => $remaining
        ]);
    }

    // ================= STORE =================
    public function store(Request $request, ShowTime $showTime)
    {
        $lockKey = 'booking_lock_' . sha1(
            $request->ip() . json_encode($request->phones ?? []) . $showTime->id
        );

        if (!Cache::add($lockKey, true, 20)) {
            return back()->withErrors([
                'general' => '⏳ الطلب قيد المعالجة بالفعل'
            ])->withInput();
        }

        try {

            // ✅ VALIDATION
            $request->validate([
                'names' => ['required','array'],
                'names.*' => ['required','string','max:255'],
                'phones' => ['required','array'],
                'phones.*' => ['required','string','min:8','max:20'],
                'payment_screenshot' => 'required|image|max:16000',
            ]);

            // 🔥 حساب التذاكر المتاحة
            $reserved = $showTime->bookings()
                ->whereIn('status', ['approved','pending'])
                ->sum('tickets_count');

            $remaining = max(0, $showTime->total_tickets - $reserved);

            $ticketsCount = count($request->names);

            // ❌ منع الحجز لو أكتر من المتاح
            if ($ticketsCount > $remaining) {
                return back()->withErrors([
                    'general' => '❌ المتاح فقط: ' . $remaining . ' تذاكر'
                ])->withInput();
            }

            // ❌ لو خلصت
            if ($remaining <= 0) {
                return back()->withErrors([
                    'general' => '❌ لا توجد تذاكر متاحة'
                ])->withInput();
            }

            // 📞 أول رقم
            $mainPhone = $this->normalizeEgyptPhone($request->phones[0]);

            // ☁️ رفع الصورة
            $file = $request->file('payment_screenshot');
            $tempPath = sys_get_temp_dir() . '/' . uniqid();
            file_put_contents($tempPath, file_get_contents($file->getRealPath()));

            $upload = (new UploadApi())->upload($tempPath, [
                'folder' => 'payments/screenshots'
            ]);

            @unlink($tempPath);

            // ✅ إنشاء الحجز
            $booking = Booking::create([
                'show_time_id' => $showTime->id,
                'full_name'    => $request->names[0],
                'phone'        => $mainPhone,
                'tickets_count'=> $ticketsCount,
                'total_price'  => $showTime->ticket_price * $ticketsCount,
                'transfer_screenshot_path' => $upload['secure_url'],
                'transfer_screenshot_public_id' => $upload['public_id'],
                'status'       => 'pending',
                'reference_code' => 'SRC-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
            ]);

            // 🎟️ إنشاء التذاكر
            foreach ($request->names as $i => $name) {
                \App\Models\Ticket::create([
                    'booking_id' => $booking->id,
                    'name'       => $name,
                    'phone'      => $this->normalizeEgyptPhone($request->phones[$i]),
                    'ticket_code'=> 'TIC-' . strtoupper(Str::random(6)),
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
            'phone' => 'رقم غير صحيح',
        ]);
    }
}



