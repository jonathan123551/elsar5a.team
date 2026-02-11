<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    public function __construct()
    {
        Configuration::instance([
            'cloud' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                'api_key'    => env('CLOUDINARY_API_KEY'),
                'api_secret' => env('CLOUDINARY_API_SECRET'),
            ],
            'url' => ['secure' => true],
        ]);
    }

    /* =======================
     |  ADMIN LIST
     ======================= */
    public function index(Request $request)
    {
        $bookings = Booking::with('showTime.show')
            ->latest()
            ->get();

        return view('admin.bookings.index', compact('bookings'));
    }

    public function show(Booking $booking)
    {
        $booking->load('showTime.show');
        return view('admin.bookings.show', compact('booking'));
    }

    /* =======================
     |  APPROVE BOOKING
     ======================= */
    public function approve(Booking $booking)
    {
        if ($booking->status === 'approved') {
            return back()->with('status', 'الحجز معتمد بالفعل');
        }

        $booking->load('showTime.show');
        $show = $booking->showTime?->show;

        if (!$show || !$show->ticket_template_path) {
            return back()->with('status', 'لا يوجد قالب تذكرة لهذا العرض');
        }

        try {

            // approve
            $booking->update([
                'status'      => 'approved',
                'approved_at' => now(),
            ]);

            /* === Generate QR === */
            $qr = Builder::create()
                ->writer(new PngWriter())
                ->data($booking->reference_code)
                ->size($show->ticket_qr_size ?? 220)
                ->margin(0)
                ->build();

            $templateImage = imagecreatefromstring(
                file_get_contents($show->ticket_template_path)
            );

            $qrImage = imagecreatefromstring($qr->getString());

            imagecopy(
                $templateImage,
                $qrImage,
                $show->ticket_qr_x ?? 0,
                $show->ticket_qr_y ?? 0,
                0,
                0,
                imagesx($qrImage),
                imagesy($qrImage)
            );

            $tempPath = sys_get_temp_dir() . '/' . $booking->reference_code . '.png';
            imagepng($templateImage, $tempPath);

            imagedestroy($templateImage);
            imagedestroy($qrImage);

            /* === Upload to Cloudinary === */
            $upload = (new UploadApi())->upload($tempPath, [
                'folder' => 'tickets/generated',
            ]);

            unlink($tempPath);

            /* === Save === */
            $booking->update([
                'qr_code_path'      => $upload['secure_url'],
                'qr_code_public_id' => $upload['public_id'],
                'whatsapp_sent'     => false,
                'whatsapp_sent_at'  => null,
            ]);

            /* === Send template message === */
            $this->sendTicketTemplate($booking->phone);

            return redirect()
                ->route('admin.bookings.show', $booking->id)
                ->with('status', 'تم اعتماد الحجز وتم إرسال رسالة واتساب');

        } catch (\Throwable $e) {

            Log::error('APPROVE FAILED', [
                'error' => $e->getMessage()
            ]);

            return back()->with('status', 'حدث خطأ أثناء اعتماد الحجز');
        }
    }

    /* =======================
     |  TEMPLATE MESSAGE
     ======================= */
    private function sendTicketTemplate($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        try {

            Http::timeout(10)
                ->connectTimeout(5)
                ->withToken(env('WHATSAPP_TOKEN'))
                ->post(
                    'https://graph.facebook.com/v23.0/' . env('WHATSAPP_PHONE_ID') . '/messages',
                    [
                        'messaging_product' => 'whatsapp',
                        'to' => $phone,
                        'type' => 'template',
                        'template' => [
                            'name' => 'ticket',
                            'language' => ['code' => 'ar_EG'],
                        ],
                    ]
                );

        } catch (\Throwable $e) {

            Log::error('WHATSAPP TEMPLATE FAILED', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /* =======================
     |  IMAGE SEND (WEBHOOK)
     ======================= */
    public function sendWhatsAppTicket($phone, $imageUrl, $reference, $full_name, $showTimeText)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        try {

            $response = Http::timeout(10)
                ->connectTimeout(5)
                ->withToken(env('WHATSAPP_TOKEN'))
                ->post(
                    'https://graph.facebook.com/v23.0/' . env('WHATSAPP_PHONE_ID') . '/messages',
                    [
                        'messaging_product' => 'whatsapp',
                        'to' => $phone,
                        'type' => 'image',
                        'image' => [
                            'link' => $imageUrl,
                            'caption' =>
                                "🎟️ أهلاً {$full_name}\n\n"
                                ."موعد الحفلة:\n{$showTimeText}\n\n"
                                ."يرجى إحضار هذه التذكرة عند الدخول.",
                        ],
                    ]
                );

            Log::info('WHATSAPP SEND RESPONSE', [
                'status' => $response->status(),
                'body'   => $response->json(),
            ]);

        } catch (\Throwable $e) {

            Log::error('WHATSAPP IMAGE FAILED', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /* =======================
     |  REJECT BOOKING
     ======================= */
    public function reject(Booking $booking)
    {
        if ($booking->status === 'rejected') {
            return back()->with('status', 'الحجز مرفوض بالفعل');
        }

        if ($booking->status === 'approved') {
            return back()->with('status', 'لا يمكن رفض حجز تم اعتماده');
        }

        $booking->update([
            'status' => 'rejected',
            'rejected_at' => now(),
        ]);

        return redirect()
            ->route('admin.bookings.index')
            ->with('status', 'تم رفض الحجز بنجاح ❌');
    }
}
