<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Archive;
use App\Models\ArchiveImage;
use Illuminate\Http\Request;
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;
use App\Support\UploadCompressor;

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
            'url' => ['secure' => true],
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
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'video_url'     => 'nullable|string|max:255',
            'facebook_reel' => 'nullable|string|max:255',
            'year'          => 'nullable|integer|min:1900|max:2100',
            'poster'        => 'nullable|image|max:20480',
            'images.*'      => 'nullable|image|max:20480',
        ]);

        // 🎬 Facebook Reel → Embed
        if (!empty($data['facebook_reel']) &&
            !str_contains($data['facebook_reel'], 'plugins/video.php')) {
            $data['facebook_reel'] =
                'https://www.facebook.com/plugins/video.php?href=' .
                urlencode($data['facebook_reel']) .
                '&show_text=false';
        }

        $uploader = new UploadApi();

        // 🖼️ Poster — compress server-side first.
        if ($request->hasFile('poster')) {
            $posterPath = UploadCompressor::compress(
                $request->file('poster'),
                maxEdge: 2400,
                quality: 85,
            );

            $poster = $uploader->upload(
                $posterPath,
                ['folder' => 'archives/posters']
            );

            if ($posterPath !== $request->file('poster')->getRealPath()) {
                @unlink($posterPath);
            }

            $data['poster_path'] = $poster['secure_url'];
            $data['poster_public_id'] = $poster['public_id'];
        }

        $archive = Archive::create($data);

        // 📸 Gallery — each image compressed individually so a
        // batch of 10 phone photos doesn't take 5 minutes to upload.
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $galleryPath = UploadCompressor::compress(
                    $image,
                    maxEdge: 2200,
                    quality: 82,
                );

                $uploaded = $uploader->upload(
                    $galleryPath,
                    ['folder' => 'archives/gallery']
                );

                if ($galleryPath !== $image->getRealPath()) {
                    @unlink($galleryPath);
                }

                ArchiveImage::create([
                    'archive_id'      => $archive->id,
                    'image_path'      => $uploaded['secure_url'],
                    'image_public_id' => $uploaded['public_id'],
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
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'video_url'     => 'nullable|string|max:255',
            'facebook_reel' => 'nullable|string|max:255',
            'year'          => 'nullable|integer|min:1900|max:2100',
            'poster'        => 'nullable|image|max:20480',
            'images.*'      => 'nullable|image|max:20480',
        ]);

        if (!empty($data['facebook_reel']) &&
            !str_contains($data['facebook_reel'], 'plugins/video.php')) {
            $data['facebook_reel'] =
                'https://www.facebook.com/plugins/video.php?href=' .
                urlencode($data['facebook_reel']) .
                '&show_text=false';
        }

        $uploader = new UploadApi();

        // 🔄 Update poster (same compression policy as store()).
        if ($request->hasFile('poster')) {
            if ($archive->poster_public_id) {
                $uploader->destroy($archive->poster_public_id);
            }

            $posterPath = UploadCompressor::compress(
                $request->file('poster'),
                maxEdge: 2400,
                quality: 85,
            );

            $poster = $uploader->upload(
                $posterPath,
                ['folder' => 'archives/posters']
            );

            if ($posterPath !== $request->file('poster')->getRealPath()) {
                @unlink($posterPath);
            }

            $data['poster_path'] = $poster['secure_url'];
            $data['poster_public_id'] = $poster['public_id'];
        }

        $archive->update($data);

        // ➕ Add new gallery images (same compression policy as
        // store()).
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $galleryPath = UploadCompressor::compress(
                    $image,
                    maxEdge: 2200,
                    quality: 82,
                );

                $uploaded = $uploader->upload(
                    $galleryPath,
                    ['folder' => 'archives/gallery']
                );

                if ($galleryPath !== $image->getRealPath()) {
                    @unlink($galleryPath);
                }

                ArchiveImage::create([
                    'archive_id'      => $archive->id,
                    'image_path'      => $uploaded['secure_url'],
                    'image_public_id' => $uploaded['public_id'],
                ]);
            }
        }

        return redirect()
            ->route('admin.archive.index')
            ->with('status', 'تم تحديث العرض بنجاح ✏️');
    }

    public function destroy(Archive $archive)
    {
        $uploader = new UploadApi();

        // 🗑️ Poster
        if ($archive->poster_public_id) {
            $uploader->destroy($archive->poster_public_id);
        }

        // 🗑️ Gallery images
        foreach ($archive->images as $img) {
            if ($img->image_public_id) {
                $uploader->destroy($img->image_public_id);
            }
            $img->delete();
        }

        $archive->delete();

        return redirect()
            ->route('admin.archive.index')
            ->with('status', 'تم حذف العرض وكل صوره من Cloudinary 🗑️');
    }
}
