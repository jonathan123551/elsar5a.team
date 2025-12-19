<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use App\Models\Show;
use App\Models\About;

class SiteController extends Controller
{
    public function home()
    {
        $shows = Show::where('is_active', true)->latest()->get();
        return view('shows.index', compact('shows'));
    }

    public function about()
    {
        $about = About::first();
        return view('about', compact('about'));
    }

    // صفحة العروض السابقة (الكروت)
    public function archive()
    {
        $archives = Archive::latest()->get();
        return view('site.archive', compact('archives'));
    }

    // صفحة تفاصيل عرض سابق
    public function archiveShow(Archive $archive)
{
    $archive->load('images');

    return view('site.archive-show', compact('archive'));
}

}
