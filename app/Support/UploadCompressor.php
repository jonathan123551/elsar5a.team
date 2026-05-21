<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * Server-side downscale + JPEG re-encode for uploaded images.
 *
 * The booking / show / archive flows let users upload photos straight
 * off a modern phone (a 12-megapixel iPhone JPEG is ~5 MB; a single
 * heavy Android photo can be 15-20 MB). Streaming that raw to
 * Cloudinary from the Railway worker has two real-world problems:
 *
 *   1. The user's browser-to-server upload is dog slow on cellular,
 *      and the more bytes we ask for the more often the request
 *      hangs / 504s mid-upload.
 *   2. Once we DO have the file, we then make a synchronous HTTPS
 *      POST to Cloudinary with the full payload — that round trip
 *      is also proportional to file size and burns request time the
 *      user is staring at a spinner for.
 *
 * Compressing on the server side BEFORE the Cloudinary call kills
 * both costs in one shot. A 18 MB iPhone shot becomes ~700 KB at
 * 2400 px / quality 82 with no human-visible quality loss for
 * posters, ticket templates, payment screenshots, or archive
 * galleries. We never upscale — small images pass through unchanged.
 *
 * Implementation uses raw GD (ext-gd is required in composer.json
 * and already installed in the Docker image) rather than
 * Intervention\Image so we don't pull in another layer and stay
 * tolerant of the worker's 512 MB memory cap.
 */
class UploadCompressor
{
    /**
     * Compress an uploaded image file to a temp JPEG and return the
     * path to the compressed file.
     *
     * On any failure (non-image, corrupt file, GD OOM, etc.) we log
     * a warning and return the original file's path — the upload
     * flow still works, it's just unoptimised. We never throw,
     * because a slightly-bigger upload is always better UX than a
     * 500.
     *
     * @param  UploadedFile  $file
     * @param  int  $maxEdge   max width or height in pixels (longer
     *                         edge is scaled to this; aspect ratio
     *                         preserved; never upscales)
     * @param  int  $quality   JPEG quality, 1-100 (82-88 is the
     *                         sweet spot for photos)
     * @return string          absolute path to the compressed file,
     *                         or the original path if compression
     *                         was skipped
     */
    public static function compress(UploadedFile $file, int $maxEdge = 2400, int $quality = 85): string
    {
        $source = $file->getRealPath();
        if (!$source || !is_readable($source)) {
            return $source ?: '';
        }

        $info = @getimagesize($source);
        if ($info === false) {
            return $source;
        }

        $mime = $info['mime'] ?? '';
        $img = match ($mime) {
            'image/jpeg', 'image/jpg', 'image/pjpeg' => @imagecreatefromjpeg($source),
            'image/png'  => @imagecreatefrompng($source),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($source) : null,
            'image/gif'  => @imagecreatefromgif($source),
            default      => null,
        };

        if (!$img) {
            return $source;
        }

        $w = imagesx($img);
        $h = imagesy($img);

        if (max($w, $h) > $maxEdge) {
            $scale = $maxEdge / max($w, $h);
            $newW = max(1, (int) round($w * $scale));
            $newH = max(1, (int) round($h * $scale));

            $resized = imagecreatetruecolor($newW, $newH);

            // Flatten any PNG transparency onto white so the JPEG
            // re-encode doesn't ship a black background.
            $white = imagecolorallocate($resized, 255, 255, 255);
            imagefilledrectangle($resized, 0, 0, $newW, $newH, $white);

            imagecopyresampled($resized, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);
            imagedestroy($img);
            $img = $resized;
        } elseif ($mime === 'image/png') {
            // Same flatten-onto-white treatment for PNGs we keep at
            // native size — JPEG can't represent alpha.
            $flat = imagecreatetruecolor($w, $h);
            $white = imagecolorallocate($flat, 255, 255, 255);
            imagefilledrectangle($flat, 0, 0, $w, $h, $white);
            imagecopy($flat, $img, 0, 0, 0, 0, $w, $h);
            imagedestroy($img);
            $img = $flat;
        }

        $out = tempnam(sys_get_temp_dir(), 'cmp_');
        if ($out === false) {
            imagedestroy($img);
            return $source;
        }
        $outJpg = $out . '.jpg';
        @rename($out, $outJpg);

        $ok = imagejpeg($img, $outJpg, $quality);
        imagedestroy($img);

        if (!$ok || !file_exists($outJpg) || filesize($outJpg) < 1024) {
            // Compression produced something silly — fall back to
            // the original so the user still gets a working upload.
            @unlink($outJpg);
            Log::warning('UploadCompressor: encode failed, falling back to original', [
                'source' => $source,
                'mime' => $mime,
            ]);
            return $source;
        }

        // Sanity log so we can see real-world byte savings in
        // Railway logs without having to instrument every call site.
        Log::info('UploadCompressor: compressed image', [
            'mime'        => $mime,
            'orig_bytes'  => @filesize($source),
            'new_bytes'   => filesize($outJpg),
            'orig_dim'    => $w . 'x' . $h,
            'max_edge'    => $maxEdge,
            'quality'     => $quality,
        ]);

        return $outJpg;
    }
}
