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

    // 申請関連のルート
    Route::prefix('requests')->name('requests.')->group(function () {
        // 一般ユーザー用のルート
        Route::middleware(['auth'])->group(function () {
            Route::get('/', [ApprovalRequestController::class, 'userIndex'])
                ->name('index');  // /requests
            Route::get('/create/{attendance}', [ApprovalRequestController::class, 'create'])
                ->name('create'); // /requests/create/{attendance}
            Route::post('/', [ApprovalRequestController::class, 'store'])
                ->name('store');  // /requests (POST)
        });

        // 管理者用のルート
        Route::middleware(['admin'])->group(function () {
            Route::get('/admin', [ApprovalRequestController::class, 'adminIndex'])
                ->name('admin.index'); // /requests/admin
            Route::patch('/{request}/approve', [ApprovalRequestController::class, 'approve'])
                ->name('approve');      // /requests/{request}/approve
            Route::patch('/{request}/reject', [ApprovalRequestController::class, 'reject'])
                ->name('reject');       // /requests/{request}/reject
        });
    });
});

require __DIR__.'/auth.php';
