<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ProfileController;
use App\Models\Attendance;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;


// トップページ
Route::get('/', function () {
    if (Auth::check()) {
        if (Auth::user()->user_type === 'admin') {
            return view('admin.index');
        } else {
            $today = Carbon::now();
            $attendance = Attendance::where('user_id', Auth::id())
                ->where('date', $today->toDateString())
                ->first();
            return view('user.index', compact('attendance'));
        }
    }
    return redirect('login');
});

// プロフィール関連
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clockIn');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clockOut');
});

require __DIR__.'/auth.php';
