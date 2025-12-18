<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Archive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ArchiveController extends Controller
{
    public function index()
    {
        $archives = Archive::latest()->get();
        return view('admin.archive.index', compact('archives'));
    }

    public function create()
    {
        return view('admin.archive.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'poster' => 'nullable|image|max:4096',
            'video_url' => 'nullable|string|max:255',
            'show_date' => 'nullable|date',
        ]);

        if ($request->hasFile('poster')) {
            $data['poster_path'] = $request->file('poster')
                ->store('archives', 'public');
        }

        Archive::create($data);

        return redirect()
            ->route('admin.archive.index')
            ->with('status', 'تم إضافة العرض للأرشيف ✅');
    }
}
