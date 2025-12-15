<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

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
    // منع إعادة الاعتماد
    if ($booking->status !== 'pending') {
        return back()->with('status', 'تم التعامل مع هذا الحجز من قبل');
    }

    $time = $booking->showTime;
    if (!$time || $time->available_tickets < $booking->tickets_count) {
        return back()->with('status', 'عدد التذاكر المتاحة غير كافٍ');
    }

    // 1️⃣ حدّث الحجز فورًا (حتى لو QR فشل)
    $booking->update([
        'status' => 'approved',
        'approved_at' => now(),
    ]);

    $time->decrement('available_tickets', $booking->tickets_count);

    // 2️⃣ توليد QR باستخدام GD فقط (بدون أي مكتبة)
    try {
        $qrText = $booking->reference_code;
        $size = 300;

        $im = imagecreatetruecolor($size, $size);
        $white = imagecolorallocate($im, 255, 255, 255);
        $black = imagecolorallocate($im, 0, 0, 0);
        imagefill($im, 0, 0, $white);

        // QR بسيط (placeholder مضمون)
        imagestring($im, 5, 20, 140, $qrText, $black);

        $relativePath = "tickets/{$qrText}.png";
        $fullPath = storage_path('app/public/' . $relativePath);

        imagepng($im, $fullPath);
        imagedestroy($im);

        $booking->update([
            'qr_code_path' => $relativePath
        ]);

    } catch (\Throwable $e) {
        logger()->error('QR failed: ' . $e->getMessage());
    }

    return redirect()
        ->route('admin.bookings.show', $booking)
        ->with('status', 'تم اعتماد الحجز بنجاح ✅');
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
