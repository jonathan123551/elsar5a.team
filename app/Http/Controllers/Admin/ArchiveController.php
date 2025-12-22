<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Archive;
use App\Models\ArchiveImage;
use Illuminate\Http\Request;

class ArchiveController extends Controller
{
    public function index()
    {
        $archives = Archive::with('images')->latest()->get();
        return view('admin.archive.index', compact('archives'));
    }

    public function create()
    {
        return view('admin.archive.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'video_url'   => 'nullable|string|max:255',
            'year'        => 'nullable|integer|min:1900|max:2100',
            'poster'      => 'nullable|image|max:4096',
            'images.*'    => 'nullable|image|max:4096',
        ]);

        // 🖼️ Poster
        if ($request->hasFile('poster')) {
            $poster = cloudinary()->upload(
                $request->file('poster')->getRealPath(),
                ['folder' => 'archives/posters']
            );

            $data['poster_path'] = $poster->getSecurePath();
        }

        $archive = Archive::create($data);

        // 📸 Gallery images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $uploaded = cloudinary()->upload(
                    $image->getRealPath(),
                    ['folder' => 'archives/gallery']
                );

                ArchiveImage::create([
                    'archive_id' => $archive->id,
                    'image_path' => $uploaded->getSecurePath(),
                ]);
            }
        }

        return redirect()
            ->route('admin.archive.index')
            ->with('status', 'تم إضافة العرض السابق بنجاح ✅');
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
            'poster'      => 'nullable|image|max:4096',
            'images.*'    => 'nullable|image|max:4096',
        ]);

        // 🖼️ Update poster
        if ($request->hasFile('poster')) {
            $poster = cloudinary()->upload(
                $request->file('poster')->getRealPath(),
                ['folder' => 'archives/posters']
            );

            $data['poster_path'] = $poster->getSecurePath();
        }

        $archive->update($data);

        // ➕ Add new gallery images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $uploaded = cloudinary()->upload(
                    $image->getRealPath(),
                    ['folder' => 'archives/gallery']
                );

                ArchiveImage::create([
                    'archive_id' => $archive->id,
                    'image_path' => $uploaded->getSecurePath(),
                ]);
            }
        }

        return redirect()
            ->route('admin.archive.index')
            ->with('status', 'تم تحديث العرض بنجاح ✏️');
    }

    public function destroy(Archive $archive)
    {
        // حذف الصور من الداتا بيز فقط (Cloudinary URLs)
        if ($archive->images) {
            foreach ($archive->images as $img) {
                $img->delete();
            }
        }

        $archive->delete();

        return redirect()
            ->route('admin.archive.index')
            ->with('status', 'تم حذف العرض السابق 🗑️');
    }
}
