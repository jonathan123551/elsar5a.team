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

    public function approve(Booking $booking)
{
    if ($booking->status !== 'pending') {
        return back()->with('status', 'تم التعامل مع هذا الحجز من قبل');
    }

    $time = $booking->showTime()->with('show')->first();
    $show = $time?->show;

    if (!$time || $time->available_tickets < $booking->tickets_count) {
        return back()->with('status', 'عدد التذاكر المتاحة غير كافٍ');
    }

    $qrText = $booking->reference_code;
    $relativePath = "tickets/{$booking->reference_code}.png";

    // امسح QR قديم
    if ($booking->qr_code_path) {
        Storage::disk('public')->delete($booking->qr_code_path);
    }

    /**
     * 🟩 لو فيه Template تذكرة
     */
    if ($show && $show->ticket_template_path && extension_loaded('gd')) {

        $templatePath = storage_path('app/public/' . $show->ticket_template_path);

        if (file_exists($templatePath)) {
            $qrSize = $show->ticket_qr_size ?? 220;
            $x = $show->ticket_qr_x ?? 0;
            $y = $show->ticket_qr_y ?? 0;

            // QR باستخدام GD فقط
            $qrPng = QrCode::format('png')
                ->size($qrSize)
                ->margin(0)
                ->generate($qrText);

            $ticket = imagecreatefrompng($templatePath);
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

            $outputPath = storage_path('app/public/' . $relativePath);
            imagepng($ticket, $outputPath);

            imagedestroy($ticket);
            imagedestroy($qrImg);
        }

    } else {
        /**
         * 🔁 fallback → QR لوحده
         */
        $qrPng = QrCode::format('png')
            ->size(600)
            ->margin(1)
            ->generate($qrText);

        Storage::disk('public')->put($relativePath, $qrPng);
    }

    $booking->update([
        'status' => 'approved',
        'qr_code_path' => $relativePath
    ]);

    $time->decrement('available_tickets', $booking->tickets_count);

    return back()->with('status', 'تم اعتماد الحجز وتوليد التذكرة بنجاح ✅');
}


    public function reject(Request $request, Booking $booking)
    {
        $booking->update([
            'status' => 'rejected',
            'admin_notes' => $request->admin_notes
        ]);

        return back()->with('status', 'تم رفض الحجز');
    }
}
