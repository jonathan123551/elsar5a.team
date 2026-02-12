<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;

class WhatsAppWebhookController extends Controller
{
   public function handle(Request $request)
{
    return response()->json(['ok' => true]);
}

}
