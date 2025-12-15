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
            ->when($status && in_array($status, ['pending', 'approved', 'rejected']), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('reference_code', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->get();

        return view('admin.bookings.index', compact('bookings', 'status', 'search'));
    }

    public function show(Booking $booking)
    {
        $booking->load('showTime.show');
        return view('admin.bookings.show', compact('booking'));
    }

    public function approve(Booking $booking)
    {
        if ($booking->status !== 'pending') {
            return back()->with('status', 'تم التعامل مع هذا الحجز من قبل.');
        }

        $time = $booking->showTime()->with('show')->first();
        $show = $time?->show;

        if (!$time || $time->available_tickets < $booking->tickets_count) {
            return back()->with('status', 'عدد التذاكر المتاحة أقل من المطلوب.');
        }

        $qrText = $booking->reference_code;

        // حذف QR القديم لو موجود
        if ($booking->qr_code_path) {
            Storage::disk('public')->delete($booking->qr_code_path);
        }

        $relativePath = null;

        /**
         * 1️⃣ محاولة دمج QR مع Template (GD فقط)
         */
        if ($show && $show->ticket_template_path && extension_loaded('gd')) {

            $templateFullPath = storage_path('app/public/' . $show->ticket_template_path);

            if (file_exists($templateFullPath)) {
                try {
                    $qrSize = $show->ticket_qr_size ?? 220;
                    $x      = $show->ticket_qr_x ?? 0;
                    $y      = $show->ticket_qr_y ?? 0;

                    $qrPng = QrCode::format('png')
                        ->size($qrSize)
                        ->margin(0)
                        ->generate($qrText);

                    $ticket = imagecreatefrompng($templateFullPath);
                    $qrImg  = imagecreatefromstring($qrPng);

                    imagecopy(
                        $ticket,
                        $qrImg,
                        $x,
                        $y,
                        0,
                        0,
                        imagesx($qrImg),
                        imagesy($qrImg)
                    );

                    $relativePath = "tickets/{$booking->reference_code}.png";
                    $outputFull   = storage_path('app/public/' . $relativePath);

                    imagepng($ticket, $outputFull);

                    imagedestroy($ticket);
                    imagedestroy($qrImg);
                } catch (\Throwable $e) {
                    $relativePath = null;
                }
            }
        }

        /**
         * 2️⃣ fallback → QR لوحده
         */
        if (!$relativePath) {
            $qrPng = QrCode::format('png')
                ->size(600)
                ->margin(1)
                ->generate($qrText);

            $relativePath = "tickets/{$booking->reference_code}.png";
            Storage::disk('public')->put($relativePath, $qrPng);
        }

        // تحديث الحجز
        $booking->status       = 'approved';
        $booking->qr_code_path = $relativePath;
        $booking->save();

        // تقليل عدد التذاكر
        $time->available_tickets -= $booking->tickets_count;
        $time->save();

        return back()->with('status', 'تم اعتماد الحجز وتوليد التذكرة بنجاح ✅');
    }

    public function reject(Request $request, Booking $booking)
    {
        if ($booking->status !== 'pending') {
            return back()->with('status', 'تم التعامل مع هذا الحجز من قبل.');
        }

        $booking->status      = 'rejected';
        $booking->admin_notes = $request->input('admin_notes');
        $booking->save();

        return back()->with('status', 'تم رفض الحجز.');
    }
}
