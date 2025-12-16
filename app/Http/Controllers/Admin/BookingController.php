<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $search = $request->query('search');

        $bookings = Booking::with('showTime.show')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($search, function ($q) use ($search) {
                $q->where('full_name', 'like', "%$search%")
                  ->orWhere('phone', 'like', "%$search%")
                  ->orWhere('reference_code', 'like', "%$search%");
            })
            ->latest()
            ->get();

        return view('admin.bookings.index', compact('bookings'));
    }
    public function show(Booking $booking)
{
    $booking->load('showTime.show');

    return view('admin.bookings.show', compact('booking'));
}

 public function approve(Booking $booking)
{
    // تأكد إن الحجز لسه pending
    if ($booking->status !== 'pending') {
        return back()->with('status', 'تم التعامل مع الحجز مسبقًا');
    }

    $time = $booking->showTime;
    if (!$time || $time->available_tickets < $booking->tickets_count) {
        return back()->with('status', 'عدد التذاكر غير كافٍ');
    }

    /** ===============================
     * 1️⃣ اعتماد الحجز
     * =============================== */
    $booking->update([
        'status'       => 'approved',
        'approved_at'  => now(),
    ]);

    $time->decrement('available_tickets', $booking->tickets_count);

    /** ===============================
     * 2️⃣ توليد QR باستخدام GD فقط
     * =============================== */
    $qrText = $booking->reference_code;
    $fileName = "tickets/{$qrText}.png";
    $fullPath = storage_path("app/public/{$fileName}");

    // إنشاء صورة QR بسيطة (بدون أي مكتبات)
    $size = 300;
    $img = imagecreatetruecolor($size, $size);
    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 0, 0, 0);

    imagefill($img, 0, 0, $white);

    // رسم QR كـ Text (حل مؤقت لكن شغال 100%)
    imagestring(
        $img,
        5,
        20,
        140,
        $qrText,
        $black
    );

    // حفظ الصورة
    imagepng($img, $fullPath);
    imagedestroy($img);

    /** ===============================
     * 3️⃣ حفظ مسار الـ QR
     * =============================== */
    $booking->update([
        'qr_code_path' => $fileName,
    ]);

    return redirect()
        ->route('admin.bookings.show', $booking->id)
        ->with('status', 'تم اعتماد الحجز وتوليد التذكرة بنجاح ✅');
}








    public function reject(Request $request, Booking $booking)
    {
        $booking->update([
            'status' => 'rejected',
            'admin_notes' => $request->admin_notes
        ]);

        return redirect()
    ->route('admin.bookings.show', $booking->id)
    ->with('status', 'تم رفض الحجز');
}
}
