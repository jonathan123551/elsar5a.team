<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Archive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ArchiveController extends Controller
{
    // عرض كل العروض السابقة
    public function index()
    {
        $archives = Archive::latest()->get();
        return view('admin.archive.index', compact('archives'));
    }

    // فورم الإضافة
    public function create()
    {
        return view('admin.archive.create');
    }

    // حفظ عرض جديد
    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'video_url'   => 'nullable|string|max:255',
            'year'   => 'nullable|intger',
            'poster'      => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('poster')) {
            $data['poster_path'] =
                $request->file('poster')->store('archives', 'public');
        }

        Archive::create($data);

        return redirect()
            ->route('admin.archive.index')
            ->with('status', 'تم إضافة العرض بنجاح ✅');
    }

    // فورم التعديل
    public function edit(Archive $archive)
    {
        return view('admin.archive.edit', compact('archive'));
    }

    // حفظ التعديل
    public function update(Request $request, Archive $archive)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'video_url'   => 'nullable|string|max:255',
            'year'   => 'nullable|intger',
            'poster'      => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('poster')) {
            if ($archive->poster_path) {
                Storage::disk('public')->delete($archive->poster_path);
            }

            $data['poster_path'] =
                $request->file('poster')->store('archives', 'public');
        }

        $archive->update($data);

        return redirect()
            ->route('admin.archive.index')
            ->with('status', 'تم تحديث العرض بنجاح ✨');
    }
}
