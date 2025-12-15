<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\About;
use Illuminate\Http\Request;

class AboutController extends Controller
{
    // فورم التعديل
    public function edit()
    {
        // هيجيب أول صف، ولو مش موجود هيعمل واحد فاضي
        $about = About::first();

        if (! $about) {
            $about = About::create([
                'description'  => '',
                'youtube'      => null,
                'instagram'    => null,
                'facebook'     => null,
                'founded_year' => null,
            ]);
        }

        return view('admin.about.edit', compact('about'));
    }

    // حفظ التعديلات
    public function update(Request $request)
    {
        $data = $request->validate([
            'description'  => ['required', 'string'],
            'youtube'      => ['nullable', 'string', 'max:255'],
            'instagram'    => ['nullable', 'string', 'max:255'],
            'facebook'     => ['nullable', 'string', 'max:255'],
            'founded_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
        ]);

        $about = About::first();

        if (! $about) {
            $about = new About();
        }

        // هنا بيملا الأعمدة ويعمل save فعلاً
        $about->fill($data);
        $about->save();

        return redirect()
            ->route('admin.about.edit')
            ->with('status', 'تم حفظ التعديلات بنجاح.');
    }
}
