<?php

namespace App\Http\Controllers;

use App\Models\Show;
use App\Models\About;

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
        $shows = Show::where('is_active', false)->latest()->get();
        return view('site.archive', compact('shows'));
    }
}
