<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Show;
use App\Models\ShowTime;
use Illuminate\Http\Request;

class ShowTimeController extends Controller
{
    // عرض كل المواعيد لعرض معيّن
    public function index(Show $show)
    {
        $times = $show->showTimes()->orderBy('date')->orderBy('time')->get();

        return view('admin.show_times.index', compact('show', 'times'));
    }

    // فورم إضافة معاد جديد
    public function create(Show $show)
    {
        return view('admin.show_times.create', compact('show'));
    }

    // حفظ معاد جديد
    public function store(Request $request, Show $show)
{
    $data = $request->validate([
        'date'             => ['required', 'date'],
        'time'             => ['required'],
        'ticket_price'     => ['required', 'numeric', 'min:0'],
        'total_tickets'    => ['required', 'integer', 'min:1'],
        'available_tickets'=> ['nullable', 'integer', 'min:0'],
        'is_sold_out'      => ['nullable', 'boolean'],
    ]);

    $show->showTimes()->create([
    'date'         => $data['date'],
    'time'         => $data['time'],
    'ticket_price' => $data['ticket_price'],
    'total_tickets'=> $data['total_tickets'],
    'is_sold_out'  => $request->boolean('is_sold_out'),
]);

    return redirect()
        ->route('admin.shows.times.index', $show)
        ->with('status', 'تم إضافة الموعد بنجاح.');
}



    // فورم تعديل معاد
    public function edit(Show $show, ShowTime $showTime)
    {
        return view('admin.show_times.edit', compact('show', 'showTime'));
    }

    // تحديث بيانات معاد
    public function update(Request $request, Show $show, ShowTime $showTime)
    {
        $data = $request->validate([
            'date'            => ['required', 'date'],
            'time'            => ['required'],
            'ticket_price'    => ['required', 'numeric', 'min:0'],
            'total_tickets'   => ['required', 'integer', 'min:1'],
            'available_tickets' => ['nullable', 'integer', 'min:0'],
            'is_sold_out'     => ['nullable'],
        ]);

        $showTime->date = $data['date'];
        $showTime->time = $data['time'];
        $showTime->ticket_price = $data['ticket_price'];
        $showTime->total_tickets = $data['total_tickets'];

        // لو الإدمن ما كتبش رقم متاح → نخلي المتاح = الإجمالي
        $showTime->is_sold_out = $request->has('is_sold_out');

        $showTime->save();

        return redirect()
            ->route('admin.shows.times.index', $show)
            ->with('status', 'تم تحديث الموعد بنجاح.');
    }
        // تحديث عدد التذاكر لمعاد معيّن من صفحة تعديل العرض
    public function updateTickets(Request $request, ShowTime $showTime)
    {
        // فاليديشين بسيط
        $data = $request->validate([
            'total_tickets' => ['required', 'integer', 'min:1'],
        ]);

        // التذاكر المحجوزة (pending + approved) لهذا المعاد
        $reserved = $showTime->bookings()
            ->whereIn('status', ['pending', 'approved'])
            ->sum('tickets_count');

        $newTotal = $data['total_tickets'];

        // نحدّث الإجمالي والمتاح
        $showTime->total_tickets     = $newTotal;
        $remaining = max(0, $newTotal - $reserved);
        $showTime->is_sold_out = ($remaining <= 0);

        $showTime->save();

        return back()->with('status', 'تم تحديث عدد التذاكر لهذا الموعد ✅');
    }

    // حذف معاد
    public function destroy(Show $show, ShowTime $showTime)
    {
        $showTime->delete();

        return redirect()
            ->route('admin.shows.times.index', $show)
            ->with('status', 'تم حذف الموعد.');
    }
    public function toggle(Show $show, ShowTime $showTime)
{
    // قلب الحالة
    $showTime->is_sold_out = !$showTime->is_sold_out;

    $showTime->save();

    return back()->with('status', 'تم تحديث حالة الموعد ✅');
}
}
