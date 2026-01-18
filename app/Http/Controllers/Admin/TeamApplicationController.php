<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TeamApplication;
use Illuminate\Http\Request;

class TeamApplicationController extends Controller
{
    public function index(Request $request)
    {
        $query = TeamApplication::query();

        // 🔍 Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%$search%")
                  ->orWhere('phone', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%");
            });
        }

        // 🎓 Filter: Education Stage
        if ($request->filled('education_stage')) {
            $query->where('education_stage', $request->education_stage);
        }

        // 🎭 Filter: Department
        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        $applications = $query->latest()->paginate(15)->withQueryString();

        return view('admin.team_applications.index', compact('applications'));
    }
}
