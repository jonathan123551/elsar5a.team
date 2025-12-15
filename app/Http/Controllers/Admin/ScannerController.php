<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class ScannerController extends Controller
{
    // صفحة السكان
    public function index()
    {
        return view('admin.scanner');
    }

    // فحص الكود اللي جاي من الـ QR أو من الإدخال اليدوي
    public function check(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $raw = trim($data['code']);

        // نحاول نطلع reference_code من النص اللي جاي من الـ QR
        // لو الكود جاي بالشكل REF=SRC-XXXX
        $ref = $raw;

        if (str_starts_with($raw, 'REF=')) {
            $ref = trim(substr($raw, 4));
        } elseif (str_starts_with($raw, 'Booking:')) {
            // لو في صيغة قديمة Booking: SRC-XXXX
            $ref = trim(substr($raw, strlen('Booking:')));
        }

        $booking = Booking::with('showTime.show')
            ->where('reference_code', $ref)
            ->first();

        // الكود مش موجود في النظام
        if (! $booking) {
            return response()->json([
                'status'  => 'error',
                'message' => '❌ الكود غير موجود في النظام.',
            ], 404);
        }

        // الحجز مش approved
        if ($booking->status !== 'approved') {
            return response()->json([
                'status'  => 'error',
                'message' => '❌ الحجز موجود لكن لسه مش Approved من الأدمن.',
            ]);
        }

        // نجهز البيانات المشتركة اللي هنرجعها في الحالتين
        $time = $booking->showTime;

        $payload = [
            'reference_code' => $booking->reference_code,
            'full_name'      => $booking->full_name,
            'show_title'     => optional($time->show)->title ?? '',
            'date'           => optional($time->date)->format('d/m/Y'),
            'time'           => $time->time
                ? Carbon::parse($time->time)->format('g:i A')  // 12 ساعة
                : '',
            'tickets_count'  => $booking->tickets_count,
            'booking_status' => $booking->status,
            'checked_in_at'  => $booking->checked_in_at
                ? Carbon::parse($booking->checked_in_at)->format('d/m/Y g:i A') // تاريخ + 12 ساعة
                : null,
        ];

        // التذكرة اتعمل لها Scan قبل كده
        if ($booking->checked_in_at) {
            return response()->json(array_merge([
                'status'  => 'used',
                'message' => '⚠️ التذكرة دي تم استخدامها قبل كده عند الدخول.',
            ], $payload));
        }

        // أول مرة دخول → نعتبره check-in
        $booking->checked_in_at = Carbon::now();     // بيتخزن UTC أو حسب إعدادات Laravel
        $booking->save();

        // نحدّث قيمة checked_in_at بعد الحفظ
        $payload['checked_in_at'] = Carbon::parse($booking->checked_in_at)->format('d/m/Y g:i A');

        return response()->json(array_merge([
            'status'  => 'ok',
            'message' => '✅ تذكرة صالحة – دخول مسموح.',
        ], $payload));
    }
}
