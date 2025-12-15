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
    // 🔒 لو اتراجع قبل كده
    if ($booking->status !== 'pending') {
        return back()->with('status', 'تم التعامل مع هذا الحجز من قبل');
    }

    $time = $booking->showTime;

    if (!$time || $time->available_tickets < $booking->tickets_count) {
        return back()->with('status', 'عدد التذاكر المتاحة غير كافٍ');
    }

    /* =========================
       1️⃣ UPDATE DB FIRST
    ==========================*/
    $booking->update([
        'status' => 'approved',
        'approved_at' => now(),
    ]);

    $time->decrement('available_tickets', $booking->tickets_count);

    /* =========================
       2️⃣ TRY QR (OPTIONAL)
    ==========================*/
    try {
        $qrText = $booking->reference_code;
        $relativePath = "tickets/{$qrText}.png";

        $qrPng = QrCode::format('png')
    ->size(300)
    ->margin(0)
    ->generate($booking->reference_code);

$relativePath = "tickets/{$booking->reference_code}.png";

Storage::disk('public')->put($relativePath, $qrPng);

$booking->update([
    'qr_code_path' => $relativePath
]);

    } catch (\Throwable $e) {
        logger()->error('QR generation failed', [
            'booking_id' => $booking->id,
            'error' => $e->getMessage(),
        ]);
    }

    /* =========================
       3️⃣ REDIRECT
    ==========================*/
    return redirect()
        ->route('admin.bookings.show', $booking->id)
        ->with('status', 'تم اعتماد الحجز بنجاح');
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
