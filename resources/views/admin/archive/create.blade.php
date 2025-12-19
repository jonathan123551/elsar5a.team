@extends('layouts.app')

@section('title', 'إضافة عرض سابق')

@section('content')
<section class="max-w-xl mx-auto space-y-6">

<h1 class="text-2xl font-bold">➕ إضافة عرض سابق</h1>

<form method="POST" enctype="multipart/form-data"
      action="{{ route('admin.archive.store') }}"
      class="space-y-4 bg-black/40 p-5 rounded-xl border border-white/10">
@csrf

<input name="title" placeholder="اسم العرض"
       class="w-full px-3 py-2 rounded bg-black/40 border border-white/10">

<textarea name="description" rows="4"
          placeholder="وصف العرض"
          class="w-full px-3 py-2 rounded bg-black/40 border border-white/10"></textarea>

<input name="video_url" placeholder="لينك يوتيوب"
       class="w-full px-3 py-2 rounded bg-black/40 border border-white/10">

<input type="number" name="year" placeholder="سنة العرض"
       class="w-full px-3 py-2 rounded bg-black/40 border border-white/10">

<label class="text-xs">بوستر العرض</label>
<input type="file" name="poster" accept="image/*">

<label class="text-xs">صور من العرض</label>
<input type="file" name="images[]" multiple accept="image/*">

<button class="px-4 py-2 bg-amber-400 text-black rounded-full">
    حفظ العرض
</button>

</form>
</section>
@endsection
