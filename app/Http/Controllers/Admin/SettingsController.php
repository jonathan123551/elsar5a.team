<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    // صفحة الفورم
    public function editPayments()
    {
        $transferWallet = Setting::get('transfer_wallet', '');
        $transferInsta  = Setting::get('transfer_insta', '');

        return view('admin.settings.payments', compact('transferWallet', 'transferInsta'));
    }

    // حفظ التعديلات
    public function updatePayments(Request $request)
    {
        $data = $request->validate([
            'transfer_wallet' => ['nullable', 'string', 'max:50'],
            'transfer_insta'  => ['nullable', 'string', 'max:100'],
        ]);

        \App\Models\Setting::set('transfer_wallet', $data['transfer_wallet'] ?? '');
        \App\Models\Setting::set('transfer_insta',  $data['transfer_insta']  ?? '');

        return back()->with('status', 'تم حفظ بيانات التحويل بنجاح ✅');
    }
}
