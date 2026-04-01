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

    $code = trim($data['code']);

    $ticket = \App\Models\Ticket::with('booking.showTime.show')
        ->where('ticket_code', $code)
        ->first();

    if (!$ticket) {
        return response()->json([
            'status' => 'error',
            'message' => 'غير موجود',
        ]);
    }

    if ($ticket->booking->status !== 'approved') {
        return response()->json([
            'status' => 'error',
            'message' => 'غير معتمد',
        ]);
    }

    $time = $ticket->booking->showTime;

    $payload = [
        'name' => $ticket->name,
        'phone' => $ticket->phone,
        'show_title' => optional($time->show)->title ?? '',
        'date' => optional($time->date)->format('d/m/Y'),
        'time' => $time->time
            ? \Carbon\Carbon::parse($time->time)->format('g:i A')
            : '',
        'scanned_at' => $ticket->scanned_at
            ? \Carbon\Carbon::parse($ticket->scanned_at)->format('g:i A')
            : null,
    ];

    // ✅ لو مستخدمة قبل كده
    if ($ticket->scanned_at) {
        return response()->json(array_merge([
            'status' => 'used',
            'message' => 'تم استخدامها',
        ], $payload));
    }

    // ✅ أول مرة
    $ticket->scanned_at = now();
    $ticket->is_scanned = true;
    $ticket->save();

    $payload['scanned_at'] = now()->format('g:i A');

    return response()->json(array_merge([
        'status' => 'ok',
        'message' => 'دخول مسموح',
    ], $payload));
}
}