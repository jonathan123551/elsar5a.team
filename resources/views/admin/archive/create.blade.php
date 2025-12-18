<form method="POST" enctype="multipart/form-data">
@csrf

<input name="title" placeholder="اسم العرض" required>

<textarea name="description" placeholder="وصف العرض"></textarea>

<input name="video_url" placeholder="لينك يوتيوب">

<input name="year" type="number" placeholder="سنة العرض">

<label>بوستر العرض</label>
<input type="file" name="poster" accept="image/*">

<label>صور من العرض (متعددة)</label>
<input type="file" name="images[]" multiple accept="image/*">

<button>حفظ</button>
</form>
