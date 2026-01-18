<?php

namespace App\Http\Controllers;

use App\Models\TeamApplication;
use Illuminate\Http\Request;

class TeamApplicationController extends Controller
{
    public function create()
    {
        return view('team.apply');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name'         => 'required|string|max:255',
            'phone'             => 'required|string|max:20',
            'email'             => 'required|email',
            'age'               => 'required|integer|min:10|max:60',
            'education_stage'   => 'required|string',
            'school_or_college' => 'nullable|string|max:255',
            'address'           => 'required|string|max:255',
            'confession_father' => 'required|string|max:255',
            'services'          => 'nullable|string',
            'preparation_class' => 'required|boolean',
            'department'        => 'required|string',
            'why_join'          => 'required|string',
        ]);

        // ✅ الحفظ الصح
        TeamApplication::create($data);

        return redirect()
            ->route('team.apply')
            ->with('success', 'تم إرسال طلبك بنجاح 🎉');
    }
}
