<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Archive;
use App\Models\ArchiveImage;
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
        'title' => 'required|string',
        'description' => 'nullable|string',
        'video_url' => 'nullable|string',
        'year' => 'nullable|integer',
        'poster' => 'nullable|image',
        'images.*' => 'nullable|image',
    ]);

    // حفظ البوستر
    if ($request->hasFile('poster')) {
        $data['poster_path'] = $request->file('poster')->store('archives/posters', 'public');
    }

    $archive = Archive::create($data);

    // حفظ الصور المتعددة
    if ($request->hasFile('images')) {
        foreach ($request->file('images') as $image) {
            ArchiveImage::create([
                'archive_id' => $archive->id,
                'image_path' => $image->store('archives/images', 'public'),
            ]);
        }
    }

    return redirect()->route('admin.archive.index')
        ->with('status', 'تم إضافة العرض بنجاح');
}

    public function edit(Archive $archive)
    {
        $archive->load('images');
        return view('admin.archive.edit', compact('archive'));
    }

    public function update(Request $request, Archive $archive)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'video_url'   => 'nullable|string|max:255',
            'year'        => 'nullable|integer|min:1900|max:2100',
            'poster'      => 'nullable|image|max:2048',
            'images.*'    => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('poster')) {
            if ($archive->poster_path) {
                Storage::disk('public')->delete($archive->poster_path);
            }

            $data['poster_path'] =
                $request->file('poster')->store('archives/posters', 'public');
        }

        $archive->update($data);

        // إضافة صور جديدة للجاليري
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                ArchiveImage::create([
                    'archive_id' => $archive->id,
                    'image_path' => $image->store('archives/gallery', 'public'),
                ]);
            }
        }

        return redirect()
            ->route('admin.archive.index')
            ->with('status', 'تم التعديل بنجاح ✏️');
    }
}
