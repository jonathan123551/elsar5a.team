<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;
use Illuminate\Support\Facades\Http;

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

    $booking->load('showTime.show', 'tickets');

    $show = $booking->showTime?->show;

    if (!$show || !$show->ticket_template_path) {
        return back()->with('status', 'لا يوجد قالب تذكرة لهذا العرض');
    }

    $booking->update([
        'status'      => 'approved',
        'approved_at' => now(),
    ]);

    foreach ($booking->tickets as $ticket) {

        if ($ticket->qr_image_path) {
            continue;
        }

        /* === QR === */
        $qr = Builder::create()
            ->writer(new PngWriter())
            ->data($ticket->ticket_code)
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

        $tempPath = sys_get_temp_dir() . '/' . $ticket->ticket_code . '.png';

        imagepng($templateImage, $tempPath);

        imagedestroy($templateImage);
        imagedestroy($qrImage);

        $upload = (new UploadApi())->upload($tempPath, [
            'folder' => 'tickets/generated',
        ]);

        unlink($tempPath);

        $ticket->update([
            'qr_image_path' => $upload['secure_url'],
            'whatsapp_sent' => false // مهم جدًا
        ]);
    }

  foreach ($booking->tickets as $ticket) {

    $this->sendTicketTemplate(
        $ticket->phone
    );

    sleep(1);
}
    return redirect()
        ->route('admin.bookings.show', $booking->id)
        ->with('status', 'تم اعتماد الحجز وإرسال رسالة الاستلام ✅');
}

    /* =======================
     | TEMPLATE
     ======================= */
public function sendTicketTemplate($phone)
{ 
    $phone = preg_replace('/[^0-9]/', '', $phone);

    Http::withToken(env('WHATSAPP_TOKEN'))->post(
        'https://graph.facebook.com/v23.0/' . env('WHATSAPP_PHONE_ID') . '/messages',
        [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'template',
            'template' => [
                'name' => 'ticket_clean_final',
                'language' => [
                    'code' => 'ar_EG'
                ],
                'components' => [] // 🔥 مهم جداً
            ]
        ]
    );
}

    /* =======================
     | SEND IMAGE
     ======================= */
    public function sendWhatsAppTicket($phone, $imageUrl, $reference, $full_name, $showTimeText)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        Http::withToken(env('WHATSAPP_TOKEN'))->post(
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
                            ."أنت الآن جزء من تجربة جديدة نصرخ فيها سويًا…\n\n"
                            ."ليزداد العقل وعيًا.\n\n"
                            ."نتمنى لك أمسية ثرية بالفن ✨\n\n"
                            ."نحن لا نطلب منك سوى حواسك،\n"
                            ."ولا ننتظر منك إلا أن تأتي إلى مصدر الصراخ…\n"
                            ."فهو دائمًا على المسرح 🎭\n\n"
                            ."نلتقي لنصرخ معًا،\n"
                            ."فنغيّر ما فسد،\n"
                            ."ونزرع بدلًا منه ثمرًا صالحًا ❤️\n\n"
                            ."🗓️ *موعد الحفلة:*\n"
                            ."{$showTimeText}\n\n"
                            ."‼️ *يرجى إحضار هذه التذكرة عند الدخول*",
            ],
            ]
        );
    }

    /* =======================
     | WEBHOOK (استلام التذاكر)
     ======================= */
public function receiveTicket(Request $request)
{
    $phone = $request['from'];

    // تنظيف الرقم
    $phone = preg_replace('/[^0-9]/', '', $phone);

    \Log::info('USER CLICKED', ['phone' => $phone]);

    // هات كل التذاكر اللي لسه متبعتتش لنفس الرقم
    $tickets = Ticket::where('phone', $phone)
        ->where('whatsapp_sent', false)
        ->get();

    if ($tickets->isEmpty()) {
        return response()->json(['status' => 'no tickets']);
    }

    foreach ($tickets as $ticket) {

        if (!$ticket->qr_image_path) {
            continue;
        }

        $this->sendWhatsAppTicket(
            $ticket->phone,
            $ticket->qr_image_path,
            $ticket->ticket_code,
            $ticket->name,
            ''
        );

        $ticket->update([
            'whatsapp_sent' => true
        ]);
    }

    return response()->json(['status' => 'sent']);
}
    /* =======================
     | REJECT
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
    /* =======================
 | RESEND TICKET
 ======================= */
public function resendTicket($id)
{
    $ticket = Ticket::findOrFail($id);

    if (!$ticket->qr_image_path) {
        return back()->with('status', '❌ التذكرة لم يتم إنشاؤها بعد');
    }

    $this->sendWhatsAppTicket(
        $ticket->phone,
        $ticket->qr_image_path,
        $ticket->ticket_code,
        $ticket->name,
        ''
    );

    return back()->with('status', '✅ تم إعادة إرسال التذكرة');
}
    public function delete($id)
{
    $booking = Booking::with('tickets')->findOrFail($id);

    // حذف التذاكر
    foreach ($booking->tickets as $ticket) {
        $ticket->delete();
    }

    // حذف الحجز
    $booking->delete();

    return redirect()->route('admin.bookings.index')
        ->with('status', 'تم حذف الحجز بالكامل');
}
}