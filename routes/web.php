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
| Public Routes
|--------------------------------------------------------------------------
*/

// Home
Route::get('/', [SiteController::class, 'home'])->name('home');

// Shows
Route::get('/shows', [ShowController::class, 'index'])->name('shows.index');
Route::get('/shows/{show}', [ShowController::class, 'show'])->name('shows.show');

// About & Archive
Route::get('/about', [SiteController::class, 'about'])->name('about');
Route::get('/archive', [SiteController::class, 'archive'])->name('archive');

// Booking (User)
Route::get('/book/{showTime}', [BookingController::class, 'create'])->name('bookings.create');
Route::post('/book/{showTime}', [BookingController::class, 'store'])->name('bookings.store');


/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Route::get('/login', [AuthController::class, 'show'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Shows
    |--------------------------------------------------------------------------
    */
    Route::get('/shows', [AdminShowController::class, 'index'])->name('shows.index');
    Route::get('/shows/create', [AdminShowController::class, 'create'])->name('shows.create');
    Route::post('/shows', [AdminShowController::class, 'store'])->name('shows.store');
    Route::get('/shows/{show}/edit', [AdminShowController::class, 'edit'])->name('shows.edit');
    Route::put('/shows/{show}', [AdminShowController::class, 'update'])->name('shows.update');
    Route::delete('/shows/{show}', [AdminShowController::class, 'destroy'])->name('shows.destroy');
    Route::post('/shows/{show}/toggle', [AdminShowController::class, 'toggleActive'])->name('shows.toggle');

    /*
    |--------------------------------------------------------------------------
    | About / Archive
    |--------------------------------------------------------------------------
    */
    Route::get('/about', [AboutController::class, 'edit'])->name('about.edit');
    Route::post('/about', [AboutController::class, 'update'])->name('about.update');
    Route::get('/archive', [ArchiveController::class, 'index'])->name('archive.index');

    /*
    |--------------------------------------------------------------------------
    | Show Times
    |--------------------------------------------------------------------------
    */
    Route::get('/shows/{show}/times', [AdminShowTimeController::class, 'index'])->name('shows.times.index');
    Route::get('/shows/{show}/times/create', [AdminShowTimeController::class, 'create'])->name('shows.times.create');
    Route::post('/shows/{show}/times', [AdminShowTimeController::class, 'store'])->name('shows.times.store');
    Route::get('/shows/{show}/times/{showTime}/edit', [AdminShowTimeController::class, 'edit'])->name('shows.times.edit');
    Route::put('/shows/{show}/times/{showTime}', [AdminShowTimeController::class, 'update'])->name('shows.times.update');
    Route::delete('/shows/{show}/times/{showTime}', [AdminShowTimeController::class, 'destroy'])->name('shows.times.destroy');

    Route::patch(
        '/show-times/{showTime}/update-tickets',
        [AdminShowTimeController::class, 'updateTickets']
    )->name('show-times.update-tickets');

    /*
    |--------------------------------------------------------------------------
    | Bookings  ✅ (الترتيب الصح)
    |--------------------------------------------------------------------------
    */
    Route::prefix('bookings')->name('bookings.')->group(function () {

        Route::get('/', [AdminBookingController::class, 'index'])->name('index');

        // ✅ approve / reject قبل show
        Route::post('/{booking}/approve', [AdminBookingController::class, 'approve'])->name('approve');
        Route::post('/{booking}/reject', [AdminBookingController::class, 'reject'])->name('reject');

        // ❗ show في الآخر
        Route::get('/{booking}', [AdminBookingController::class, 'show'])->name('show');
    });

    /*
    |--------------------------------------------------------------------------
    | Scanner
    |--------------------------------------------------------------------------
    */
    Route::get('/scanner', [ScannerController::class, 'index'])->name('scanner');
    Route::post('/scanner/check', [ScannerController::class, 'check'])->name('scanner.check');

    /*
    |--------------------------------------------------------------------------
    | Payments Settings
    |--------------------------------------------------------------------------
    */
    Route::get('/settings/payments', [SettingsController::class, 'editPayments'])
        ->name('settings.payments.edit');

    Route::post('/settings/payments', [SettingsController::class, 'updatePayments'])
        ->name('settings.payments.update');
});
