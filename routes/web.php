<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ApprovalRequestController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// トップページ
Route::get('/', [HomeController::class, 'index'])
    ->name('home');

// 認証が必要なルート
Route::middleware('auth')->group(function () {
    // プロフィール関連
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });

    // 勤怠関連
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', [AttendanceController::class, 'monthly'])->name('monthly');
        Route::post('/clock-in', [AttendanceController::class, 'clockIn'])->name('clockIn');
        Route::post('/clock-out', [AttendanceController::class, 'clockOut'])->name('clockOut');
    });

    // 申請関連のルート
    Route::prefix('requests')->name('requests.')->middleware('auth')->group(function () {
        // 共通ルート - ユーザー種別で表示を分岐
        Route::get('/', [ApprovalRequestController::class, 'index'])->name('index');

        // 一般ユーザー用のルート
        Route::get('/create/{attendance}', [ApprovalRequestController::class, 'create'])->name('create');
        Route::post('/', [ApprovalRequestController::class, 'store'])->name('store');

        // 管理者用のルート
        Route::middleware('admin')->group(function () {
            Route::patch('/{approvalRequest}/approve', [ApprovalRequestController::class, 'approve'])->name('approve');
            Route::patch('/{approvalRequest}/reject', [ApprovalRequestController::class, 'reject'])->name('reject');
        });
    });
});

require __DIR__.'/auth.php';
