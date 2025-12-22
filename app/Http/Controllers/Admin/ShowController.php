<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Show;
use Illuminate\Http\Request;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class ShowController extends Controller
{
    public function index()
    {
        $shows = Show::latest()->get();
        return view('admin.shows.index', compact('shows'));
    }

    public function create()
    {
        return view('admin.shows.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'description'     => 'nullable|string',
            'poster'          => 'nullable|image|max:4096',
            'ticket_template' => 'nullable|image|max:8192',
            'ticket_qr_x'     => 'nullable|integer|min:0',
            'ticket_qr_y'     => 'nullable|integer|min:0',
            'ticket_qr_size'  => 'nullable|integer|min:10',
            'is_active'       => 'nullable|boolean',
        ]);

        // 🎭 Poster
        if ($request->hasFile('poster')) {
            $poster = Cloudinary::upload(
                $request->file('poster')->getRealPath(),
                ['folder' => 'shows/posters']
            );
            $data['poster_path'] = $poster->getSecurePath();
        }

        // 🎟️ Ticket template
        if ($request->hasFile('ticket_template')) {
            $ticket = Cloudinary::upload(
                $request->file('ticket_template')->getRealPath(),
                ['folder' => 'tickets/templates']
            );
            $data['ticket_template_path'] = $ticket->getSecurePath();
        }

        $show = Show::create([
            'title'                => $data['title'],
            'description'          => $data['description'] ?? null,
            'poster_path'          => $data['poster_path'] ?? null,
            'ticket_template_path' => $data['ticket_template_path'] ?? null,
            'ticket_qr_x'          => $data['ticket_qr_x'] ?? 0,
            'ticket_qr_y'          => $data['ticket_qr_y'] ?? 0,
            'ticket_qr_size'       => $data['ticket_qr_size'] ?? 220,
            'is_active'            => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.shows.times.index', $show)
            ->with('status', 'تم إضافة العرض بنجاح 🎉');
    }

    public function edit(Show $show)
    {
        return view('admin.shows.edit', compact('show'));
    }

    public function update(Request $request, Show $show)
    {
        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'description'     => 'nullable|string',
            'poster'          => 'nullable|image|max:4096',
            'ticket_template' => 'nullable|image|max:8192',
            'ticket_qr_x'     => 'nullable|integer|min:0',
            'ticket_qr_y'     => 'nullable|integer|min:0',
            'ticket_qr_size'  => 'nullable|integer|min:10',
            'is_active'       => 'nullable|boolean',
        ]);

        if ($request->hasFile('poster')) {
            $poster = Cloudinary::upload(
                $request->file('poster')->getRealPath(),
                ['folder' => 'shows/posters']
            );
            $show->poster_path = $poster->getSecurePath();
        }

        if ($request->hasFile('ticket_template')) {
            $ticket = Cloudinary::upload(
                $request->file('ticket_template')->getRealPath(),
                ['folder' => 'tickets/templates']
            );
            $show->ticket_template_path = $ticket->getSecurePath();
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
            ->with('status', 'تم تحديث العرض بنجاح ✨');
    }

    public function destroy(Show $show)
    {
        $show->delete();

        return redirect()
            ->route('admin.shows.index')
            ->with('status', 'تم حذف العرض 🗑️');
    }

    public function toggleActive(Show $show)
    {
        $show->is_active = ! $show->is_active;
        $show->save();

        return back()->with('status', 'تم تحديث حالة العرض');
    }
}
