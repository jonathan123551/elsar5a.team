<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\ShowController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\ShowController as AdminShowController;
use App\Http\Controllers\Admin\ShowTimeController as AdminShowTimeController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ScannerController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\AboutController;
use App\Http\Controllers\Admin\ArchiveController;

/*
|--------------------------------------------------------------------------
| Public Routes (User)
|--------------------------------------------------------------------------
*/

// صفحة الهوم / الداشبورد العام للموقع
Route::get('/', [SiteController::class, 'home'])->name('home');

// صفحة قائمة العروض الحالية (زي ما كانت عندك)
Route::get('/shows', [ShowController::class, 'index'])->name('shows.index');

// صفحة About
Route::get('/about', [SiteController::class, 'about'])->name('about');

// صفحة العروض السابقة / الأرشيف
Route::get('/archive', [SiteController::class, 'archive'])->name('archive');

// صفحة تفاصيل العرض
Route::get('/shows/{show}', [ShowController::class, 'show'])->name('shows.show');

// صفحة إنشاء حجز
Route::get('/book/{showTime}', [BookingController::class, 'create'])->name('bookings.create');

// إرسال طلب الحجز
Route::post('/book/{showTime}', [BookingController::class, 'store'])->name('bookings.store');


/*
|--------------------------------------------------------------------------
| Authentication Routes (Admin Login)
|--------------------------------------------------------------------------
*/

// صفحة الدخول
Route::get('/login', [AuthController::class, 'show'])->name('login');

// تنفيذ تسجيل الدخول
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

// تسجيل خروج الأدمن
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


/*
|--------------------------------------------------------------------------
| Admin Routes (Protected)
|--------------------------------------------------------------------------
*/
Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {

    // لوحة التحكم الرئيسية للأدمن
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | إدارة العروض
    |--------------------------------------------------------------------------
    */
    Route::get('/shows', [AdminShowController::class, 'index'])->name('shows.index');
    Route::get('/shows/create', [AdminShowController::class, 'create'])->name('shows.create');
    Route::post('/shows', [AdminShowController::class, 'store'])->name('shows.store');
    Route::get('/shows/{show}/edit', [AdminShowController::class, 'edit'])->name('shows.edit');
    Route::put('/shows/{show}', [AdminShowController::class, 'update'])->name('shows.update');
    Route::delete('/shows/{show}', [AdminShowController::class, 'destroy'])->name('shows.destroy');
    Route::post('/shows/{show}/toggle', [AdminShowController::class, 'toggleActive'])->name('shows.toggle');

    // إعداد صفحة "عن الفريق" من لوحة الأدمن
    Route::get('/about', [AboutController::class, 'edit'])->name('about.edit');
    Route::post('/about', [AboutController::class, 'update'])->name('about.update');
    Route::get('/archive', [ArchiveController::class, 'index'])->name('archive.index');
    /*
    |--------------------------------------------------------------------------
    | إدارة مواعيد العروض ShowTimes
    |--------------------------------------------------------------------------
    */
    Route::get('/shows/{show}/times', [AdminShowTimeController::class, 'index'])->name('shows.times.index');
    Route::get('/shows/{show}/times/create', [AdminShowTimeController::class, 'create'])->name('shows.times.create');
    Route::post('/shows/{show}/times', [AdminShowTimeController::class, 'store'])->name('shows.times.store');
    Route::get('/shows/{show}/times/{showTime}/edit', [AdminShowTimeController::class, 'edit'])->name('shows.times.edit');
    Route::put('/shows/{show}/times/{showTime}', [AdminShowTimeController::class, 'update'])->name('shows.times.update');
    Route::delete('/shows/{show}/times/{showTime}', [AdminShowTimeController::class, 'destroy'])->name('shows.times.destroy');

    // روت لتحديث عدد التذاكر من صفحة edit لكل ميعاد
    Route::patch('/show-times/{showTime}/update-tickets', [AdminShowTimeController::class, 'updateTickets'])
        ->name('show-times.update-tickets');

    /*
    |--------------------------------------------------------------------------
    | إدارة الحجوزات
    |--------------------------------------------------------------------------
    */
    Route::prefix('bookings')->name('bookings.')->group(function () {
        Route::get('/', [AdminBookingController::class, 'index'])->name('index');
        Route::get('/{booking}', [AdminBookingController::class, 'show'])->name('show');
        Route::post('/{booking}/approve', [AdminBookingController::class, 'approve'])->name('approve');
        Route::post('/{booking}/reject', [AdminBookingController::class, 'reject'])->name('reject');
    });

    /*
    |--------------------------------------------------------------------------
    | صفحة سكان التذاكر (QR Scanner)
    |--------------------------------------------------------------------------
    */
    Route::get('/scanner', [ScannerController::class, 'index'])->name('scanner');
    Route::post('/scanner/check', [ScannerController::class, 'check'])->name('scanner.check');

    /*
    |--------------------------------------------------------------------------
    | إعدادات التحويلات (محفظة / InstaPay)
    |--------------------------------------------------------------------------
    */
    Route::get('/settings/payments', [SettingsController::class, 'editPayments'])
        ->name('settings.payments.edit');

    Route::post('/settings/payments', [SettingsController::class, 'updatePayments'])
        ->name('settings.payments.update');
});
