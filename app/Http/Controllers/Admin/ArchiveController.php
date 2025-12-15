<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Show;

class ArchiveController extends Controller
{
    // صفحة إدارة العروض السابقة
    public function index()
    {
        // هنا باعتبار إن العروض اللي is_active = 0 هي العروض السابقة
        $archivedShows = Show::where('is_active', false)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.archive.index', compact('archivedShows'));
    }
}
