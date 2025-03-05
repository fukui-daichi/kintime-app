<?php

use App\Http\Controllers\TimecardController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// トップページ
Route::get('/', [HomeController::class, 'index'])
    ->name('home');

// 認証が必要なルート
Route::middleware('auth')->group(function () {
    // 共通ルート（管理者・一般ユーザー共通でアクセス可能）
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });

    // 一般ユーザー専用ルート
    Route::middleware('user')->group(function () {
        // 勤怠関連
        Route::prefix('timecard')->name('timecard.')->group(function () {
            Route::get('/', [TimecardController::class, 'index'])->name('index');
            Route::post('/clock-in', [TimecardController::class, 'clockIn'])->name('clockIn');
            Route::post('/clock-out', [TimecardController::class, 'clockOut'])->name('clockOut');
        });

        // 申請関連のルート（申請の作成・表示など）
        Route::prefix('requests')->name('requests.')->group(function () {
            Route::get('/', [RequestController::class, 'index'])->name('index');

            // 勤怠修正申請用ルート
            Route::get('/timecard/{timecard}', [RequestController::class, 'createTimecardModification'])
                ->name('timecard.create');

            // 有給休暇申請用ルート
            Route::get('/paid-vacation', [RequestController::class, 'createPaidVacation'])
                ->name('paid_vacation.create');

            Route::post('/', [RequestController::class, 'store'])->name('store');
        });
    });

    // 管理者専用ルート
    Route::middleware('admin')->group(function () {
        // 申請管理ルート
        Route::prefix('requests')->name('requests.')->group(function () {
            Route::get('/', [RequestController::class, 'index'])->name('index');
            Route::patch('/{request}/approve', [RequestController::class, 'approve'])->name('approve');
            Route::patch('/{request}/reject', [RequestController::class, 'reject'])->name('reject');
        });
    });
});

require __DIR__.'/auth.php';
