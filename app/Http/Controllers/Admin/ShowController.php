<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Show;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ShowController extends Controller
{
    public function index()
    {
        $shows = Show::orderByDesc('created_at')->get();

        return view('admin.shows.index', compact('shows'));
    }

    public function create()
    {
        return view('admin.shows.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'poster'           => ['nullable', 'image', 'max:4096'],

            // تصميم التذكرة + الـ QR
            'ticket_template'  => ['nullable', 'image', 'max:8192'],
            'ticket_qr_x'      => ['nullable', 'integer', 'min:0'],
            'ticket_qr_y'      => ['nullable', 'integer', 'min:0'],
            'ticket_qr_size'   => ['nullable', 'integer', 'min:10'],
            'is_active'        => ['nullable', 'boolean'],
        ]);

        $posterPath = null;
        $templatePath = null;

        // بوستر العرض
        if ($request->hasFile('poster')) {
            $posterPath = $request->file('poster')->store('posters', 'public');
        }

        // تصميم التذكرة (خلي الفولدر اسمه templates عشان يبقى ثابت)
        if ($request->hasFile('ticket_template')) {
            $templatePath = $request->file('ticket_template')->store('templates', 'public');
        }

        $show = Show::create([
            'title'                => $data['title'],
            'description'          => $data['description'] ?? null,
            'poster_path'          => $posterPath,

            'ticket_template_path' => $templatePath,
            'ticket_qr_x'          => $data['ticket_qr_x'] ?? null,
            'ticket_qr_y'          => $data['ticket_qr_y'] ?? null,
            'ticket_qr_size'       => $data['ticket_qr_size'] ?? 220,

            'is_active'            => $request->boolean('is_active'),
        ]);

       return redirect()
    ->route('admin.shows.times.index', $show)
    ->with('status', 'تم إضافة العرض بنجاح. دلوقتي ضيف مواعيد العرض ✨');

    }

    public function edit(Show $show)
    {
        return view('admin.shows.edit', compact('show'));
    }

    public function update(Request $request, Show $show)
    {
        $data = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'poster'           => ['nullable', 'image', 'max:4096'],

            'ticket_template'  => ['nullable', 'image', 'max:8192'],
            'ticket_qr_x'      => ['nullable', 'integer', 'min:0'],
            'ticket_qr_y'      => ['nullable', 'integer', 'min:0'],
            'ticket_qr_size'   => ['nullable', 'integer', 'min:10'],
            'is_active'        => ['nullable', 'boolean'],
        ]);

        // بوستر جديد لو اترفع
        if ($request->hasFile('poster')) {
            if ($show->poster_path) {
                Storage::disk('public')->delete($show->poster_path);
            }
            $show->poster_path = $request->file('poster')->store('posters', 'public');
        }

        // تصميم تذكرة جديد لو اترفع
        if ($request->hasFile('ticket_template')) {
            if ($show->ticket_template_path) {
                Storage::disk('public')->delete($show->ticket_template_path);
            }
            $show->ticket_template_path = $request->file('ticket_template')->store('templates', 'public');
        }

        $show->title          = $data['title'];
        $show->description    = $data['description'] ?? null;
        $show->ticket_qr_x    = $data['ticket_qr_x'] ?? 0;
        $show->ticket_qr_y    = $data['ticket_qr_y'] ?? 0;
        $show->ticket_qr_size = $data['ticket_qr_size'] ?? 220;
        $show->is_active      = $request->boolean('is_active');

        $show->save();

        return redirect()
            ->route('admin.shows.edit', $show)
            ->with('status', 'تم تحديث العرض بنجاح.');
    }

    public function destroy(Show $show)
    {
        // ممكن كمان تمسح البوستر / التذكرة لو حابب
        if ($show->poster_path) {
            Storage::disk('public')->delete($show->poster_path);
        }
        if ($show->ticket_template_path) {
            Storage::disk('public')->delete($show->ticket_template_path);
        }

        $show->delete();

        return redirect()
            ->route('admin.shows.index')
            ->with('status', 'تم حذف العرض.');
    }

    public function toggleActive(Show $show)
    {
        $show->is_active = ! $show->is_active;
        $show->save();

        return back()->with('status', 'تم تحديث حالة العرض.');
    }
}
