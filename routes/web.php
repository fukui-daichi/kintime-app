<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// トップページ
Route::get('/', function () {
    if (Auth::check()) {
        // ユーザー種別によってリダイレクト先を変更
        return Auth::user()->user_type === 'admin'
            ? redirect()->route('admin.index')
            : redirect()->route('user.index');
    }
    return redirect('login');
});

// 管理者用ルート
Route::prefix('admin')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/', function () {
        return view('admin.index');
    })->name('admin.index');
});

// 一般ユーザー用ルート
Route::prefix('user')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/', function () {
        return view('user.index');
    })->name('user.index');
});

// プロフィール関連（既存）
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
