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
    // 🛑 لو متوافق قبل كده – متعملش حاجة
    if ($booking->status === 'approved') {
        return back()->with('status', 'الحجز معتمد بالفعل');
    }

    // 1️⃣ حدّث الحالة الأول
    $booking->update([
        'status' => 'approved',
        'approved_at' => now(),
    ]);

    // 2️⃣ تأكد إن فولدر tickets موجود
    if (!Storage::disk('public')->exists('tickets')) {
        Storage::disk('public')->makeDirectory('tickets');
    }

    // 3️⃣ توليد QR
    $relativePath = "tickets/{$booking->reference_code}.png";

    $qrPng = QrCode::format('png')
        ->size(300)
        ->margin(1)
        ->generate($booking->reference_code);

    Storage::disk('public')->put($relativePath, $qrPng);

    // 4️⃣ حفظ مسار الـ QR
    $booking->update([
        'qr_code_path' => $relativePath,
    ]);

    return redirect()
        ->route('admin.bookings.show', $booking->id)
        ->with('status', 'تم اعتماد الحجز وتوليد التذكرة بنجاح');
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
