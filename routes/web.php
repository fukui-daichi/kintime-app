<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TimecardController;
use App\Http\Controllers\TimecardUpdateRequestController;
use Illuminate\Support\Facades\Route;

// メインダッシュボード
Route::get('/', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// 認証済みユーザー向けルート
Route::middleware('auth')->group(function () {
    // プロファイル管理
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    // タイムカード管理
    Route::prefix('timecard')->group(function () {
        // 打刻操作
        Route::post('/clock-in', [TimecardController::class, 'clockIn'])->name('timecard.clock-in');
        Route::post('/clock-out', [TimecardController::class, 'clockOut'])->name('timecard.clock-out');
        Route::post('/break-start', [TimecardController::class, 'startBreak'])->name('timecard.break-start');
        Route::post('/break-end', [TimecardController::class, 'endBreak'])->name('timecard.break-end');

        // 勤怠一覧・編集
        Route::get('/', [TimecardController::class, 'index'])->name('timecard.index');
        Route::get('/{timecard}/edit', [TimecardController::class, 'edit'])->name('timecard.edit');
        Route::put('/{timecard}', [TimecardController::class, 'update'])->name('timecard.update');

        // 打刻修正申請
        Route::prefix('update-requests')->name('timecard-update-requests.')->group(function () {
            Route::get('/', [TimecardUpdateRequestController::class, 'index'])->name('index');
            Route::get('/create/{timecard}', [TimecardUpdateRequestController::class, 'create'])->name('create');
            Route::post('/', [TimecardUpdateRequestController::class, 'store'])->name('store');
            Route::get('/{id}', [TimecardUpdateRequestController::class, 'show'])->name('show');
            Route::post('/{id}/approve', [TimecardUpdateRequestController::class, 'approve'])->name('approve');
        });
    });
});

require __DIR__.'/auth.php';
