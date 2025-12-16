<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
class BookingController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $search = $request->query('search');

        $bookings = Booking::with('showTime.show')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($search, function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('reference_code', 'like', "%{$search}%");
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
    if ($booking->status === 'approved') {
        return back()->with('status', 'الحجز معتمد بالفعل');
    }

    // حمّل العرض ووقت العرض
    $booking->load('showTime.show');
    $show = $booking->showTime?->show;

    if (!$show || !$show->ticket_template_path) {
        return back()->with('status', 'لا يوجد قالب تذكرة لهذا العرض');
    }

    // 1️⃣ اعتماد الحجز
    $booking->update([
        'status' => 'approved',
        'approved_at' => now(),
    ]);

    // 2️⃣ إعداد المسارات
    $templatePath = storage_path('app/public/' . $show->ticket_template_path);
    $outputPath   = "tickets/{$booking->reference_code}.png";

    if (!file_exists($templatePath)) {
        return back()->with('status', 'ملف قالب التذكرة غير موجود');
    }

    if (!Storage::disk('public')->exists('tickets')) {
        Storage::disk('public')->makeDirectory('tickets');
    }

    // 3️⃣ إحداثيات و حجم QR من الداتابيز
    $qrSize = $show->ticket_qr_size ?? 220;
    $x = $show->ticket_qr_x ?? 0;
    $y = $show->ticket_qr_y ?? 0;

    // 4️⃣ توليد QR (Endroid بدون imagick)
    $qrResult = \Endroid\QrCode\Builder\Builder::create()
        ->writer(new \Endroid\QrCode\Writer\PngWriter())
        ->data($booking->reference_code)
        ->size($qrSize)
        ->margin(0)
        ->build();

    // 5️⃣ دمج QR فوق التذكرة (GD)
    $ticketImg = imagecreatefrompng($templatePath);
    $qrImg = imagecreatefromstring($qrResult->getString());

    imagecopy(
        $ticketImg,
        $qrImg,
        $x,
        $y,
        0,
        0,
        imagesx($qrImg),
        imagesy($qrImg)
    );

    imagepng($ticketImg, storage_path("app/public/{$outputPath}"));

    imagedestroy($ticketImg);
    imagedestroy($qrImg);

    // 6️⃣ حفظ المسار في الحجز
    $booking->update([
        'qr_code_path' => $outputPath,
    ]);

    return redirect()
        ->route('admin.bookings.show', $booking->id)
        ->with('status', 'تم اعتماد الحجز وإنشاء التذكرة بنجاح 🎟️');
}



    public function reject(Request $request, Booking $booking)
    {
        $booking->update([
            'status' => 'rejected',
            'admin_notes' => $request->admin_notes,
        ]);

        return redirect()
            ->route('admin.bookings.show', $booking->id)
            ->with('status', 'تم رفض الحجز ❌');
    }
}
