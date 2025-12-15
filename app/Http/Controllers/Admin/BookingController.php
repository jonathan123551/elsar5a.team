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
    if ($booking->status !== 'pending') {
        return back()->with('status', 'تم التعامل مع هذا الحجز من قبل');
    }

    $time = $booking->showTime;
    $show = $time?->show;

    if (!$time || $time->available_tickets < $booking->tickets_count) {
        return back()->with('status', 'عدد التذاكر المتاحة غير كافٍ');
    }

    // 1️⃣ اعتماد الحجز
    $booking->update([
        'status' => 'approved',
        'approved_at' => now(),
    ]);

    $time->decrement('available_tickets', $booking->tickets_count);

    // 2️⃣ توليد التذكرة
    try {
        $templatePath = storage_path('app/public/ticket_template.png'); // 👈 صورة التذكرة
        $outputPath   = "tickets/{$booking->reference_code}.png";

        $ticket = imagecreatefrompng($templatePath);

        // توليد QR بسيط بـ GD
        $qrSize = 220;
        $qr = imagecreatetruecolor($qrSize, $qrSize);
        $white = imagecolorallocate($qr, 255, 255, 255);
        $black = imagecolorallocate($qr, 0, 0, 0);
        imagefill($qr, 0, 0, $white);
        imagestring($qr, 5, 20, 100, $booking->reference_code, $black);

        // 👇 مكان الـ QR على التذكرة
        $x = 800; // عدلها حسب التصميم
        $y = 350;

        imagecopy($ticket, $qr, $x, $y, 0, 0, $qrSize, $qrSize);

        imagepng($ticket, storage_path('app/public/' . $outputPath));

        imagedestroy($ticket);
        imagedestroy($qr);

        $booking->update([
            'qr_code_path' => $outputPath
        ]);

    } catch (\Throwable $e) {
        logger()->error($e->getMessage());
    }

    return redirect()
        ->route('admin.bookings.show', $booking)
        ->with('status', 'تم اعتماد الحجز وتوليد التذكرة ✅');
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
