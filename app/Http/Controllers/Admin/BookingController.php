<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateTicketImageJob;
use App\Jobs\SendWhatsAppTicketImageJob;
use App\Jobs\SendWhatsAppTicketTemplateJob;
use App\Models\Booking;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Cloudinary\Configuration\Configuration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Support\CloudinaryUrl;

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

    // Build each ticket's QR image ahead of time (idempotent on
    // qr_image_path) so it's ready in Cloudinary by the time the customer
    // taps "أستلام التذكرة". The heavy GD + Cloudinary work no longer blocks
    // the admin's request — it runs in GenerateTicketImageJob.
    foreach ($booking->tickets as $ticket) {
        GenerateTicketImageJob::dispatch($ticket->id)->onQueue('high');
    }

    // ONE template per UNIQUE phone — not one per ticket. A 50-ticket
    // booking on the same number now sends a single "أستلام التذكرة"
    // template instead of 50 identical ones. The customer's single tap
    // then drains every remaining ticket for that phone (see
    // WhatsAppWebhookController::handle).
    $uniquePhones = $booking->tickets
        ->pluck('phone')
        ->map(fn ($phone) => preg_replace('/[^0-9]/', '', (string) $phone))
        ->filter()
        ->unique()
        ->values();

    foreach ($uniquePhones as $phone) {
        SendWhatsAppTicketTemplateJob::dispatch($phone)->onQueue('high');
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

        // Run the image through a WhatsApp-safe Cloudinary
        // transformation: forces JPEG, caps the long edge at
        // 1600 px, and picks an aggressive-but-clean quality.
        // This guarantees the link WhatsApp fetches is under
        // their ~5 MB silent-drop cap even if the underlying
        // ticket is huge.
        $imageUrl = CloudinaryUrl::forWhatsApp($imageUrl);

        $response = Http::withToken(env('WHATSAPP_TOKEN'))->post(
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

        if (!$response->successful()) {
            // WhatsApp errors used to fail silently — a ticket would
            // be marked as sent even when the Cloud API rejected
            // the image (too large, bad format, expired token,
            // etc.). Log the structured error so we can diagnose
            // future failures from Railway logs.
            Log::error('whatsapp: send failed', [
                'phone'   => $phone,
                'image'   => $imageUrl,
                'status'  => $response->status(),
                'body'    => $response->json() ?? $response->body(),
            ]);
        }

        return $response;
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

        $response = $this->sendWhatsAppTicket(
            $ticket->phone,
            $ticket->qr_image_path,
            $ticket->ticket_code,
            $ticket->name,
            ''
        );

        // Only mark as sent if WhatsApp actually accepted the
        // image. Previously every send was marked successful even
        // when the Cloud API silently dropped the image because
        // the file was too big — and the user's "resend" click
        // would no-op because the ticket was already flagged
        // sent.
        if ($response && $response->successful()) {
            $ticket->update([
                'whatsapp_sent' => true
            ]);
        }
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

    // Admin force-resend: reopen the ticket for delivery, then enqueue. The
    // job's atomic claim still guards against this colliding with a concurrent
    // webhook tap, so the customer never gets a duplicate.
    $ticket->update(['whatsapp_sent' => false, 'delivery_status' => 'pending']);
    SendWhatsAppTicketImageJob::dispatch($ticket->id)->onQueue('high');

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

    /**
     * Public, unauthenticated landing for /ticket/{reference}.
     *
     * Historically the route mapped to a non-existent method, so any
     * hit returned an HTTP 500 (and, with APP_DEBUG=true, the full
     * Laravel ignition stack). The route is orphaned — nothing in the
     * UI links to it — so we keep it harmless by:
     *   - returning a small Arabic status page when the reference
     *     resolves to a real booking (no PII beyond what the booker
     *     already knows), and
     *   - aborting with a themed 404 when it doesn't.
     *
     * NOTE: this does NOT trigger any WhatsApp send. The WhatsApp
     * delivery flow is intentionally untouched.
     */
    public function sendTicketsByReference(string $reference)
    {
        $reference = trim($reference);

        $booking = Booking::with('showTime.show')
            ->where('reference_code', $reference)
            ->first();

        if (!$booking) {
            abort(404);
        }

        return view('site.ticket-status', compact('booking'));
    }
}
