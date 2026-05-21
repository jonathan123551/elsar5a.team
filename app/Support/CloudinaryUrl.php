<?php

namespace App\Support;

/**
 * Cloudinary URL transformation helpers.
 *
 * Cloudinary lets you ask for a derived version of an asset by
 * inserting a transformation segment into the delivery URL, e.g.
 *
 *   https://res.cloudinary.com/<cloud>/image/upload/<TRANSFORM>/v.../path.png
 *
 * That derived asset is generated lazily on first hit and cached on
 * Cloudinary's CDN. We use this to guarantee a WhatsApp-safe variant
 * of every generated ticket without re-uploading anything.
 */
class CloudinaryUrl
{
    /**
     * Build a WhatsApp-safe variant of a Cloudinary image URL.
     *
     * WhatsApp Cloud API silently drops images that are:
     *   - over 5 MB
     *   - in formats it can't render reliably
     *   - excessively large in pixel dimensions
     *
     * We force JPEG, cap the long edge at 1600 px, and let
     * Cloudinary pick an aggressive-but-clean quality. This brings
     * a typical 4-8 MB generated ticket PNG down to ~200-400 KB
     * while keeping the QR code crisp enough to scan from a phone
     * screen.
     *
     * Transformation breakdown:
     *   c_limit  — only DOWNSCALE; never upscales tiny tickets
     *   w_1600   — cap long edge at 1600 px
     *   h_1600   — same cap on the other edge (defensive)
     *   q_auto:good — Cloudinary's quality picker (good = balanced)
     *   f_jpg    — force JPEG so WhatsApp never sees an alpha PNG
     *               that it might reject; QR-on-poster doesn't need
     *               transparency anyway
     *   fl_progressive — progressive JPEG so first paint on slow
     *               mobile data shows the ticket faster
     */
    public static function forWhatsApp(string $url): string
    {
        if ($url === '' || !str_contains($url, '/image/upload/')) {
            return $url;
        }

        $transform = 'c_limit,w_1600,h_1600,q_auto:good,f_jpg,fl_progressive';

        // If the URL already has a transformation block (e.g.
        // someone passed in a derived URL), bail out instead of
        // stacking transformations and producing something weird.
        $needle  = '/image/upload/';
        $pos     = strpos($url, $needle);
        $afterUp = substr($url, $pos + strlen($needle));
        $firstSeg = strtok($afterUp, '/');

        // A version segment looks like "v1234567890". Anything else
        // before the first "v..." we treat as an existing
        // transformation and leave the URL alone.
        if ($firstSeg !== false && !preg_match('/^v\d+$/', $firstSeg) && $firstSeg !== '') {
            return $url;
        }

        return substr_replace($url, $needle . $transform . '/', $pos, strlen($needle));
    }
}
