<?php

namespace App\Http\Controllers;

use App\Models\Show;
use App\Models\About;
use App\Models\Archive;
class SiteController extends Controller
{
    // الصفحة الرئيسية (Dashboard العام)
    public function home()
    {
        $shows = Show::where('is_active', true)->latest()->get();
        return view('shows.index', compact('shows'));
    }

    // صفحة About
    public function about()
{
    $about = About::first();  // 👈 دي اللي كانت ناقصة
    return view('about', compact('about'));
}

    // صفحة العروض السابقة
    

public function archive()
{
    $archives = Archive::with('images')->latest()->get();
    return view('archive', compact('archives'));
}

}
