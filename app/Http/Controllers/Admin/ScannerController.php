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

        // استخراج الكود
        $code = $raw;

        if (str_starts_with($raw, 'REF=')) {
            $code = trim(substr($raw, 4));
        }

        $ticket = Ticket::with('booking.showTime.show')
            ->where('ticket_code', $code)
            ->first();

        // ❌ مش موجود
        if (!$ticket) {
            return response()->json([
                'status'  => 'error',
                'message' => '❌ التذكرة غير موجودة',
            ], 404);
        }

        // ❌ مش approved
        if ($ticket->booking->status !== 'approved') {
            return response()->json([
                'status'  => 'error',
                'message' => '❌ الحجز لم يتم اعتماده بعد',
            ]);
        }

        $time = $ticket->booking->showTime;

        $payload = [
            'ticket_code' => $ticket->ticket_code,
            'name'        => $ticket->name,
            'phone'       => $ticket->phone,
            'show_title'  => optional($time->show)->title ?? '',
            'date'        => optional($time->date)->format('d/m/Y'),
            'time'        => $time->time
                ? Carbon::parse($time->time)->format('g:i A')
                : '',
            'scanned_at'  => $ticket->scanned_at
                ? Carbon::parse($ticket->scanned_at)->format('d/m/Y g:i A')
                : null,
        ];

        // ⚠️ مستخدمة قبل كده
        if ($ticket->scanned_at) {
            return response()->json(array_merge([
                'status'  => 'used',
                'message' => '⚠️ التذكرة مستخدمة قبل كده',
            ], $payload));
        }

        // ✅ أول مرة
        $ticket->update([
            'is_scanned' => true,
            'scanned_at' => now(),
        ]);

        $payload['scanned_at'] = now()->format('d/m/Y g:i A');

        return response()->json(array_merge([
            'status'  => 'ok',
            'message' => '✅ دخول مسموح',
        ], $payload));
    }
}