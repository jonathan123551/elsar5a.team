<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Bacon QR Code (GD only)
 */
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Renderer\Image\GdImageBackEnd;
use BaconQrCode\Writer;

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

        return view('admin.bookings.index', compact('bookings', 'status', 'search'));
    }

    public function approve(Booking $booking)
    {
        if ($booking->status !== 'pending') {
            return back()->with('status', 'تم التعامل مع هذا الحجز من قبل');
        }

        $time = $booking->showTime;
        if (!$time || $time->available_tickets < $booking->tickets_count) {
            return back()->with('status', 'عدد التذاكر غير كافٍ');
        }

        /** 🟢 QR Text */
        $qrText = $booking->reference_code;

        /** 🟢 QR Renderer (GD فقط) */
        $renderer = new ImageRenderer(
            new RendererStyle(600),
            new GdImageBackEnd()
        );

        $writer = new Writer($renderer);

        /** 🟢 Generate QR PNG */
        $qrPng = $writer->writeString($qrText);

        /** 🟢 Save QR */
        $relativePath = "tickets/{$booking->reference_code}.png";
        Storage::disk('public')->put($relativePath, $qrPng);

        /** 🟢 Update booking */
        $booking->update([
            'status'       => 'approved',
            'qr_code_path' => $relativePath,
        ]);

        /** 🟢 Update tickets */
        $time->decrement('available_tickets', $booking->tickets_count);

        return back()->with('status', 'تم اعتماد الحجز وتوليد التذكرة بنجاح ✅');
    }

    public function reject(Request $request, Booking $booking)
    {
        if ($booking->status !== 'pending') {
            return back()->with('status', 'تم التعامل مع هذا الحجز من قبل');
        }

        $booking->update([
            'status' => 'rejected',
            'admin_notes' => $request->admin_notes,
        ]);

        return back()->with('status', 'تم رفض الحجز');
    }
}
