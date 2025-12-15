<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Show;
use App\Models\ShowTime;
use App\Models\Booking;
use App\Models\Setting;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalShows     = Show::count();
        $totalShowTimes = ShowTime::count();

        // إجمالي التذاكر الأساسية لكل المواعيد
        $totalTicketsAllTimes = ShowTime::sum('total_tickets');

        // إجمالي التذاكر المحجوزة في (pending + approved)
        $bookedPendingApproved = Booking::whereIn('status', ['pending', 'approved'])
            ->sum('tickets_count');

        // التذاكر المتبقية على مستوى كل المواعيد
        $ticketsRemaining = max($totalTicketsAllTimes - $bookedPendingApproved, 0);

        // إجمالي التذاكر المعتمدة
        $totalTicketsApproved = Booking::where('status', 'approved')
            ->sum('tickets_count');

        // إجمالي الفلوس من الحجوزات المعتمدة
        $totalRevenue = Booking::where('status', 'approved')
            ->sum('total_price');

        // حالات الحجوزات
        $pendingBookings  = Booking::where('status', 'pending')->count();
        $rejectedBookings = Booking::where('status', 'rejected')->count();

        // إحصائيات لكل ميعاد عرض لوحده
        $showTimesStats = ShowTime::with('show')
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        foreach ($showTimesStats as $time) {
            // عدد التذاكر المعتمدة للميعاد ده
            $approved = Booking::where('show_time_id', $time->id)
                ->where('status', 'approved')
                ->sum('tickets_count');

            // عدد التذاكر pending للميعاد ده
            $pending = Booking::where('show_time_id', $time->id)
                ->where('status', 'pending')
                ->sum('tickets_count');

            // التذاكر المتبقية في الميعاد ده
            $remaining = $time->total_tickets - ($approved + $pending);
            if ($remaining < 0) {
                $remaining = 0;
            }

            // نضيف القيم دي كخصائص على الموديل عشان نستخدمها في الـ Blade
            $time->approved_tickets  = $approved;
            $time->pending_tickets   = $pending;
            $time->remaining_tickets = $remaining;
        }

        // 🔹 بيانات التحويل (محفوظة في جدول settings)
        $transferWallet = Setting::get('transfer_wallet', '');
        $transferInsta  = Setting::get('transfer_insta', '');

        return view('admin.dashboard', compact(
            'totalShows',
            'totalShowTimes',
            'ticketsRemaining',
            'totalTicketsApproved',
            'totalRevenue',
            'pendingBookings',
            'rejectedBookings',
            'showTimesStats',
            'transferWallet',
            'transferInsta'
        ));
    }

    /**
     * تحديث بيانات التحويل (محفظة / InstaPay) — يُستدعى من الفورم في لوحة الأدمن
     */
    public function updatePayments(Request $request)
    {
        $data = $request->validate([
            'transfer_wallet' => ['nullable', 'string', 'max:100'],
            'transfer_insta'  => ['nullable', 'string', 'max:100'],
        ]);

        // خزن في جدول settings بواسطة موديل Setting (شكل Setting.set متوقع)
        Setting::set('transfer_wallet', $data['transfer_wallet'] ?? '');
        Setting::set('transfer_insta',  $data['transfer_insta']  ?? '');

        return back()->with('status', 'تم تحديث بيانات التحويل بنجاح ✅');
    }
}
