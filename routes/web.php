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
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

    // 勤怠関連
    Route::get('/attendance', [AttendanceController::class, 'monthly'])
        ->name('attendance.monthly');  // 月別一覧画面
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])
        ->name('attendance.clockIn');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])
        ->name('attendance.clockOut');

    // 申請一覧（一般ユーザー用）
    Route::get('/requests', [ApprovalRequestController::class, 'index'])
        ->name('requests.index');

    // 申請作成フォーム
    Route::get('/requests/create/{attendance}', [ApprovalRequestController::class, 'create'])
        ->name('requests.create');

    // 申請保存
    Route::post('/requests', [ApprovalRequestController::class, 'store'])
        ->name('requests.store');

    // 管理者用ルート
    Route::middleware(['admin'])->group(function () {
        // 承認待ち一覧
        Route::get('/admin/requests', [ApprovalRequestController::class, 'adminIndex'])
            ->name('admin.requests.index');

        // 承認・否認処理
        Route::patch('/admin/requests/{request}/approve', [ApprovalRequestController::class, 'approve'])
            ->name('admin.requests.approve');
        Route::patch('/admin/requests/{request}/reject', [ApprovalRequestController::class, 'reject'])
            ->name('admin.requests.reject');
    });
});

require __DIR__.'/auth.php';
