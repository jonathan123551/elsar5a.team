<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TeamApplication;
use App\Exports\TeamApplicationsExport;
use Maatwebsite\Excel\Facades\Excel;

class TeamApplicationController extends Controller
{
    public function index()
    {
        $applications = TeamApplication::latest()->get();
        return view('admin.team_applications.index', compact('applications'));
    }

    // ✅ EXPORT EXCEL
    public function export()
    {
        return Excel::download(
            new TeamApplicationsExport,
            'team_applications.xlsx'
        );
    }
}