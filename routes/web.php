<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TimecardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // タイムカード関連ルート
    Route::prefix('timecard')->group(function () {
        Route::post('/clock-in', [TimecardController::class, 'clockIn'])->name('timecard.clock-in');
        Route::post('/clock-out', [TimecardController::class, 'clockOut'])->name('timecard.clock-out');
        Route::post('/break-start', [TimecardController::class, 'startBreak'])->name('timecard.break-start');
        Route::post('/break-end', [TimecardController::class, 'endBreak'])->name('timecard.break-end');

        // 勤怠一覧（月次・ページネーションなし）
        Route::get('/', [TimecardController::class, 'index'])->name('timecard.index');
    });
});

require __DIR__.'/auth.php';
