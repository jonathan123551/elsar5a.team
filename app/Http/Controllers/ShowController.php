<?php

namespace App\Http\Controllers;

use App\Models\Show;

class ShowController extends Controller
{
    public function index()
    {
        $shows = Show::where('is_active', true)->with('showTimes')->get();
        return view('shows.index', compact('shows'));
    }

    public function show(Show $show)
    {
        $show->load('showTimes');
        return view('shows.show', compact('show'));
    }
}
