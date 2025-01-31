<?php

namespace App\Services;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AttendanceService
{
    /**
     * 本日の勤怠データを取得
     */
    public function getTodayAttendance(int $userId)
    {
        return Attendance::where('user_id', $userId)
            ->where('date', Carbon::now()->toDateString())
            ->first();
    }

    /**
     * 出勤処理
     */
    public function clockIn(int $userId)
    {
        $now = Carbon::now();

        // 同日の打刻チェック
        if ($this->hasTodayAttendance($userId)) {
            return [
                'success' => false,
                'message' => '本日はすでに出勤打刻されています。'
            ];
        }

        Attendance::create([
            'user_id' => $userId,
            'date' => $now->toDateString(),
            'clock_in' => $now->toTimeString(),
            'status' => 'working',
        ]);

        return [
            'success' => true,
            'message' => '出勤を記録しました。'
        ];
    }

    /**
     * 退勤処理
     */
    public function clockOut(int $userId)
    {
        $now = Carbon::now();
        $attendance = $this->getTodayWorkingAttendance($userId);

        if (!$attendance) {
            return [
                'success' => false,
                'message' => '本日の出勤記録が見つかりません。'
            ];
        }

        $clockIn = Carbon::parse($attendance->clock_in);
        $workMinutes = $now->diffInMinutes($clockIn) - $attendance->break_time;

        $attendance->update([
            'clock_out' => $now->toTimeString(),
            'actual_work_time' => $workMinutes,
            'status' => 'left',
        ]);

        return [
            'success' => true,
            'message' => '退勤を記録しました。'
        ];
    }

    /**
     * 本日の勤怠データ存在チェック
     */
    private function hasTodayAttendance(int $userId): bool
    {
        return Attendance::where('user_id', $userId)
            ->where('date', Carbon::now()->toDateString())
            ->exists();
    }

    /**
     * 本日の勤務中データ取得
     */
    private function getTodayWorkingAttendance(int $userId)
    {
        return Attendance::where('user_id', $userId)
            ->where('date', Carbon::now()->toDateString())
            ->where('status', 'working')
            ->first();
    }
}
