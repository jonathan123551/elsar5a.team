<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TeamApplication;

class TeamApplicationController extends Controller
{
    public function index()
    {
        $applications = TeamApplication::latest()->paginate(20);
        return view('admin.team_applications.index', compact('applications'));
    }
}
