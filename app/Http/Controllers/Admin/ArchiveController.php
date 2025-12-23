<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Archive;
use App\Models\ArchiveImage;
use Illuminate\Http\Request;
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;

class ArchiveController extends Controller
{
    public function __construct()
    {
        Configuration::instance([
            'cloud' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                'api_key'    => env('CLOUDINARY_API_KEY'),
                'api_secret' => env('CLOUDINARY_API_SECRET'),
            ],
            'url' => [
                'secure' => true,
            ],
        ]);
    }

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
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'video_url' => 'nullable|string|max:255',
            'facebook_reel' => 'nullable|string|max:255',
            'year' => 'nullable|integer|min:1900|max:2100',
            'poster' => 'nullable|image|max:4096',
            'images.*' => 'nullable|image|max:4096',
        ]);

        // 🔁 Facebook Reel → Embed URL
        if (!empty($data['facebook_reel']) &&
            !str_contains($data['facebook_reel'], 'plugins/video.php')) {
            $data['facebook_reel'] =
                'https://www.facebook.com/plugins/video.php?href=' .
                urlencode($data['facebook_reel']) .
                '&show_text=false';
        }

        if ($request->hasFile('poster')) {
            $uploaded = (new UploadApi())->upload(
                $request->file('poster')->getRealPath(),
                ['folder' => 'archives/posters']
            );
            $data['poster_path'] = $uploaded['secure_url'];
        }

        $archive = Archive::create($data);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $uploaded = (new UploadApi())->upload(
                    $image->getRealPath(),
                    ['folder' => 'archives/gallery']
                );

                ArchiveImage::create([
                    'archive_id' => $archive->id,
                    'image_path' => $uploaded['secure_url'],
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
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'video_url' => 'nullable|string|max:255',
            'facebook_reel' => 'nullable|string|max:255',
            'year' => 'nullable|integer|min:1900|max:2100',
            'poster' => 'nullable|image|max:4096',
            'images.*' => 'nullable|image|max:4096',
        ]);

        if (!empty($data['facebook_reel']) &&
            !str_contains($data['facebook_reel'], 'plugins/video.php')) {
            $data['facebook_reel'] =
                'https://www.facebook.com/plugins/video.php?href=' .
                urlencode($data['facebook_reel']) .
                '&show_text=false';
        }

        if ($request->hasFile('poster')) {
            $uploaded = (new UploadApi())->upload(
                $request->file('poster')->getRealPath(),
                ['folder' => 'archives/posters']
            );
            $data['poster_path'] = $uploaded['secure_url'];
        }

        $archive->update($data);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $uploaded = (new UploadApi())->upload(
                    $image->getRealPath(),
                    ['folder' => 'archives/gallery']
                );

                ArchiveImage::create([
                    'archive_id' => $archive->id,
                    'image_path' => $uploaded['secure_url'],
                ]);
            }
        }

        return redirect()
            ->route('admin.archive.index')
            ->with('status', 'تم تحديث العرض بنجاح ✏️');
    }

    public function destroy(Archive $archive)
    {
        $archive->images()?->delete();
        $archive->delete();

        return redirect()
            ->route('admin.archive.index')
            ->with('status', 'تم حذف العرض السابق 🗑️');
    }
}
