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

    /**
     * APPROVE BOOKING
     * - Generate QR
     * - Upload Cloudinary
     * - Send WhatsApp TEMPLATE (ticket)
     */
    public function approve(Booking $booking)
    {
        Log::info('APPROVE HIT', ['booking_id' => $booking->id]);

        if ($booking->status === 'approved') {
            return back()->with('status', 'الحجز معتمد بالفعل');
        }

        $booking->load('showTime.show');
        $show = $booking->showTime?->show;

        if (!$show || !$show->ticket_template_path) {
            return back()->with('status', 'لا يوجد قالب تذكرة لهذا العرض');
        }

        // Approve booking
        $booking->update([
            'status'      => 'approved',
            'approved_at' => now(),
        ]);

        // QR settings
        $qrSize = $show->ticket_qr_size ?? 220;
        $x = $show->ticket_qr_x ?? 0;
        $y = $show->ticket_qr_y ?? 0;

        // Generate QR
        $qr = Builder::create()
            ->writer(new PngWriter())
            ->data($booking->reference_code)
            ->size($qrSize)
            ->margin(0)
            ->build();

        // Load template image
        $templateImage = imagecreatefromstring(
            file_get_contents($show->ticket_template_path)
        );
        $qrImage = imagecreatefromstring($qr->getString());

        imagecopy(
            $templateImage,
            $qrImage,
            $x,
            $y,
            0,
            0,
            imagesx($qrImage),
            imagesy($qrImage)
        );

        // Save temp file
        $tempPath = sys_get_temp_dir() . '/' . $booking->reference_code . '.png';
        imagepng($templateImage, $tempPath);

        imagedestroy($templateImage);
        imagedestroy($qrImage);

        // Upload to Cloudinary
        $upload = (new UploadApi())->upload($tempPath, [
            'folder' => 'tickets/generated',
        ]);

        unlink($tempPath);

        // Save QR data
        $booking->update([
            'qr_code_path'      => $upload['secure_url'],
            'qr_code_public_id' => $upload['public_id'],
        ]);

        // 🔔 SEND TEMPLATE (NO VARIABLES)
        $this->sendTicketTemplate($booking->phone);

        return redirect()
            ->route('admin.bookings.show', $booking->id)
            ->with('status', 'تم اعتماد الحجز، تم إرسال رسالة واتساب 📩');
    }

    public function reject(Request $request, Booking $booking)
    {
        $uploader = new UploadApi();

        if ($booking->transfer_screenshot_public_id) {
            $uploader->destroy($booking->transfer_screenshot_public_id);
        }

        if ($booking->qr_code_public_id) {
            $uploader->destroy($booking->qr_code_public_id);
        }

        $booking->update([
            'status'      => 'rejected',
            'admin_notes' => $request->admin_notes,
        ]);

        return redirect()
            ->route('admin.bookings.show', $booking->id)
            ->with('status', 'تم رفض الحجز ❌');
    }

    // ================= TEMPLATE (NO VARIABLES) =================
    private function sendTicketTemplate($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        $response = Http::withToken(env('WHATSAPP_TOKEN'))
            ->post(
                'https://graph.facebook.com/v23.0/' . env('WHATSAPP_PHONE_ID') . '/messages',
                [
                    'messaging_product' => 'whatsapp',
                    'to' => $phone,
                    'type' => 'template',
                    'template' => [
                        'name' => 'ticket',
                        'language' => [
                            'code' => 'ar_EG',
                        ],
                    ],
                ]
            );

        Log::info('WHATSAPP TEMPLATE RESPONSE', [
            'phone'  => $phone,
            'status' => $response->status(),
            'body'   => $response->json(),
        ]);
    }

    // ================= IMAGE (USED BY WEBHOOK) =================
    public function sendWhatsAppTicket($phone, $imageUrl, $reference, $full_name)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        $response = Http::withToken(env('WHATSAPP_TOKEN'))
            ->post(
                'https://graph.facebook.com/v23.0/' . env('WHATSAPP_PHONE_ID') . '/messages',
                [
                    'messaging_product' => 'whatsapp',
                    'to' => $phone,
                    'type' => 'image',
                    'image' => [
                        'link' => $imageUrl,
                        'caption' =>
                               "*🎟️ أهلاً {$full_name}*\n\n"
                                ."يسعدنا وجودك معنا،\n"
                                ."أنت الآن جزء من تجربة جديدة نصرخ فيها سويًا…\n"
                                ."ليزداد العقل وعيًا.\n\n"
                                ."نتمنى لك أمسية ثرية بالفن ✨\n\n"
                                ."نحن لا نطلب منك سوى حواسك،\n"
                                ."ولا ننتظر منك إلا أن تأتي إلى مصدر الصراخ…\n"
                                ."فهو دائمًا على المسرح 🎭\n\n"
                                ."نلتقي لنصرخ معًا،\n"
                                ."فنغيّر ما فسد،\n"
                                ."ونزرع بدلًا منه ثمرًا صالحًا ❤️\n\n"
                                ."‼️ *يرجى إحضار هذه التذكرة عند الدخول*",
                    ],
                ]
            );

        Log::info('WHATSAPP IMAGE RESPONSE', [
            'phone'  => $phone,
            'status' => $response->status(),
            'body'   => $response->json(),
        ]);
    }
}
