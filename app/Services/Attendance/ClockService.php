<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use Carbon\Carbon;

class ClockService
{
    /**
     * 勤怠情報に関するデータを取得
     */
    public function getAttendanceData(int $userId): array
    {
        $attendance = $this->getTodayAttendance($userId);

        return [
            'attendance' => $attendance,
            'canClockIn' => $this->canClockIn($attendance),
            'canClockOut' => $this->canClockOut($attendance),
            'attendanceData' => $this->formatAttendanceData($attendance),
        ];
    }

    /**
     * 出勤処理
     */
    public function clockIn(int $userId): array
    {
        if ($this->hasTodayAttendance($userId)) {
            return [
                'success' => false,
                'message' => '本日はすでに出勤打刻されています。'
            ];
        }

        Attendance::create([
            'user_id' => $userId,
            'date' => Carbon::now()->toDateString(),
            'clock_in' => Carbon::now()->toTimeString(),
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
    public function clockOut(int $userId): array
    {
        $attendance = $this->getTodayWorkingAttendance($userId);

        if (!$attendance) {
            return [
                'success' => false,
                'message' => '本日の出勤記録が見つかりません。'
            ];
        }

        $clockIn = Carbon::parse($attendance->clock_in);
        $clockOut = Carbon::now();
        $workMinutes = $clockIn->floatDiffInMinutes($clockOut);
        $actualWorkMinutes = $workMinutes - $attendance->break_time;

        $attendance->update([
            'clock_out' => $clockOut->format('H:i:s'),
            'actual_work_time' => (int)$actualWorkMinutes,
            'status' => 'left',
        ]);

        return [
            'success' => true,
            'message' => '退勤を記録しました。'
        ];
    }

    private function getTodayAttendance(int $userId): ?Attendance
    {
        return Attendance::where('user_id', $userId)
            ->where('date', Carbon::now()->toDateString())
            ->first();
    }

    private function canClockIn(?Attendance $attendance): bool
    {
        return !$attendance || $attendance->status !== 'working';
    }

    private function canClockOut(?Attendance $attendance): bool
    {
        return $attendance && $attendance->status === 'working';
    }

    private function hasTodayAttendance(int $userId): bool
    {
        return Attendance::where('user_id', $userId)
            ->where('date', Carbon::now()->toDateString())
            ->exists();
    }

    private function getTodayWorkingAttendance(int $userId): ?Attendance
    {
        return Attendance::where('user_id', $userId)
            ->where('date', Carbon::now()->toDateString())
            ->where('status', 'working')
            ->first();
    }

    private function formatAttendanceData(?Attendance $attendance): array
    {
        if (!$attendance) {
            return [];
        }

        $hours = floor(abs($attendance->actual_work_time) / 60);
        $minutes = abs($attendance->actual_work_time) % 60;

        return [
            'clockInTime' => $attendance->clock_in
                ? Carbon::parse($attendance->clock_in)->format('H:i')
                : '未打刻',
            'clockOutTime' => $attendance->clock_out
                ? Carbon::parse($attendance->clock_out)->format('H:i')
                : '未打刻',
            'workHours' => $attendance->actual_work_time ? $hours : null,
            'workMinutes' => $attendance->actual_work_time ? $minutes : null,
        ];
    }
}
