<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class ScannerController extends Controller
{
    public function index()
    {
        return view('admin.scanner');
    }

    public function check(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $raw = trim($data['code']);
        $code = $raw;

        if (str_starts_with($raw, 'REF=')) {
            $code = trim(substr($raw, 4));
        }

        $ticket = Ticket::with('booking.showTime.show')
            ->where('ticket_code', $code)
            ->first();

        if (!$ticket) {
            return response()->json([
                'status' => 'error',
                'message' => '❌ غير موجود',
            ]);
        }

        if ($ticket->booking->status !== 'approved') {
            return response()->json([
                'status' => 'error',
                'message' => '❌ غير معتمد',
            ]);
        }

        $time = $ticket->booking->showTime;

        $payload = [
            'name' => $ticket->name,
            'phone' => $ticket->phone,
            'show_title' => optional($time->show)->title ?? '',
            'date' => optional($time->date)->format('d/m/Y'),
            'time' => $time->time
                ? Carbon::parse($time->time)->format('g:i A')
                : '',
            'scanned_at' => $ticket->scanned_at,
        ];

        // 🔥 GRACE PERIOD (20 ثانية)
        if ($ticket->scanned_at) {

            $diff = now()->diffInSeconds($ticket->scanned_at);

            if ($diff <= 20) {
                return response()->json(array_merge([
                    'status' => 'ok',
                    'message' => '✅ تأكيد سريع',
                ], $payload));
            }

            return response()->json(array_merge([
                'status' => 'used',
                'message' => '⚠️ مستخدمة قبل كده',
            ], $payload));
        }

        // ✅ أول مرة
        $ticket->update([
            'is_scanned' => true,
            'scanned_at' => now(),
        ]);

        return response()->json(array_merge([
            'status' => 'ok',
            'message' => '✅ دخول',
        ], $payload));
    }
}