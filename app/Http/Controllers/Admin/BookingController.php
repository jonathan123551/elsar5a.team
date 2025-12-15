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
        $status = $request->query('status');   // pending / approved / rejected / null
        $search = $request->query('search');   // نص البحث

        $bookings = Booking::with('showTime.show')
            ->when($status && in_array($status, ['pending', 'approved', 'rejected']), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('reference_code', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
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
        // لو اتتعامل مع الحجز قبل كده
        if ($booking->status !== 'pending') {
            return back()->with('status', 'تم التعامل مع هذا الحجز من قبل.');
        }

        // نجيب الميعاد + العرض
        $time = $booking->showTime()->with('show')->first();
        $show = $time?->show;

        // تأكد إن في تذاكر كفاية
        if (!$time || $time->available_tickets < $booking->tickets_count) {
            return back()->with('status', 'عدد التذاكر المتاحة أقل من المطلوب.');
        }

        // نص الـ QR (كود الحجز)
        $qrText = $booking->reference_code;

        // لو فيه تذكرة قديمة، امسحها
        if ($booking->qr_code_path) {
            Storage::disk('public')->delete($booking->qr_code_path);
        }

        $relativePath = null;

        /**
         * 1) نحاول نركّب الـ QR على تصميم التذكرة باستخدام Imagick لو موجود
         */
        if (
            $show &&
            $show->ticket_template_path &&
            extension_loaded('imagick')           // نتأكد إن الإضافة موجودة
        ) {
            $templateFullPath = storage_path('app/public/' . $show->ticket_template_path);

            if (file_exists($templateFullPath)) {
                try {
                    // مقاس ومكان الـ QR
                    $qrSize = $show->ticket_qr_size ?: 220;
                    $x      = $show->ticket_qr_x ?? 0;
                    $y      = $show->ticket_qr_y ?? 0;

                    // نولّد QR كـ PNG (binary string)
                    $qrPng = QrCode::format('png')
                        ->size($qrSize)
                        ->margin(0)
                        ->generate($qrText);

                    // نقرأ صورة التذكرة
                    $ticket = new \Imagick($templateFullPath);

                    // نقرأ الـ QR من الـ string
                    $qr = new \Imagick();
                    $qr->readImageBlob($qrPng);
                    $qr->resizeImage($qrSize, $qrSize, \Imagick::FILTER_LANCZOS, 1);

                    // ندمج الـ QR على التذكرة
                    $ticket->compositeImage($qr, \Imagick::COMPOSITE_DEFAULT, $x, $y);

                    // نحفظ النتيجة في storage/app/public/tickets
                    $relativePath = "tickets/{$booking->reference_code}.png";
                    $outputFull   = storage_path('app/public/' . $relativePath);

                    $ticket->setImageFormat('png');
                    $ticket->writeImage($outputFull);

                    // نفضّي الذاكرة
                    $ticket->clear();
                    $ticket->destroy();
                    $qr->clear();
                    $qr->destroy();
                } catch (\Throwable $e) {
                    // لو حصل أي Error هنا هنسيب $relativePath = null عشان نعمل فول باك تحت
                    $relativePath = null;
                }
            }
        }

        /**
         * 2) لو لأي سبب ما قدرناش نطلع تذكرة كاملة → نطلع QR لوحده
         */
        if (!$relativePath) {
            $qrPng = QrCode::format('png')
                ->size(600)
                ->margin(1)
                ->generate($qrText);

            $relativePath = "tickets/{$booking->reference_code}.png";
            Storage::disk('public')->put($relativePath, $qrPng);
        }

        // تحديث حالة الحجز
        $booking->status       = 'approved';
        $booking->qr_code_path = $relativePath;
        $booking->save();

        // تقليل التذاكر المتاحة بعد اعتماد الحجز
        $time->available_tickets -= $booking->tickets_count;
        $time->save();

        return back()->with('status', 'تم اعتماد الحجز وتوليد تذكرة على التصميم (أو QR لوحده) ✅');
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
