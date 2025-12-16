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
    $booking->refresh();

    if ($booking->status !== 'pending') {
        return back()->with('status', 'تم التعامل مع هذا الحجز من قبل');
    }

    $time = $booking->showTime;

    if (!$time || $time->available_tickets < $booking->tickets_count) {
        return back()->with('status', 'عدد التذاكر المتاحة غير كافٍ');
    }

    // 1️⃣ اعتمد الحجز
    $booking->update([
        'status' => 'approved',
        'approved_at' => now(),
    ]);

    $time->decrement('available_tickets', $booking->tickets_count);

    // 2️⃣ ولّد QR باستخدام GD فقط
    try {
        $relativePath = "tickets/{$booking->reference_code}.png";

        $qrPng = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
            ->size(300)
            ->margin(1)
            ->generate($booking->reference_code);

        \Storage::disk('public')->put($relativePath, $qrPng);

        $booking->update([
            'qr_code_path' => $relativePath,
        ]);
    } catch (\Throwable $e) {
        \Log::error('QR generation failed: '.$e->getMessage());
    }

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
