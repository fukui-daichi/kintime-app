<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $today = Carbon::now();
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today->toDateString())
            ->first();

        return view('attendance.index', compact('attendance'));
    }

    public function clockIn()
    {
        $user = Auth::user();
        $now = Carbon::now();

        // 同日の打刻がないかチェック
        $exists = Attendance::where('user_id', $user->id)
            ->where('date', $now->toDateString())
            ->exists();

        if ($exists) {
            return back()->with('error', '本日はすでに出勤打刻されています。');
        }

        Attendance::create([
            'user_id' => $user->id,
            'date' => $now->toDateString(),
            'clock_in' => $now->toTimeString(),
            'status' => 'working',
        ]);

        return back()->with('success', '出勤を記録しました。');
    }

    public function clockOut()
    {
        $user = Auth::user();
        $now = Carbon::now();

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $now->toDateString())
            ->where('status', 'working')
            ->first();

        if (!$attendance) {
            return back()->with('error', '本日の出勤記録が見つかりません。');
        }

        // 実労働時間の計算
        $clockIn = Carbon::parse($attendance->clock_in);
        $workMinutes = $now->diffInMinutes($clockIn) - $attendance->break_time;

        $attendance->update([
            'clock_out' => $now->toTimeString(),
            'actual_work_time' => $workMinutes,
            'status' => 'left',
        ]);

        return back()->with('success', '退勤を記録しました。');
    }
}
