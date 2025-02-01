<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use Carbon\Carbon;
use App\Helpers\TimeFormatter;

class ClockService
{
    private const REGULAR_WORK_MINUTES = 480; // 8時間 = 480分
    private const NIGHT_WORK_START = 22; // 深夜時間帯開始
    private const NIGHT_WORK_END = 5;    // 深夜時間帯終了

    /**
     * 勤怠情報に関するデータを取得
     *
     * @param int $userId ユーザーID
     * @return array{
     *   attendance: ?Attendance,
     *   canClockIn: bool,
     *   canClockOut: bool,
     *   attendanceData: array
     * }
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
     * 出勤打刻処理
     *
     * @param int $userId ユーザーID
     * @return array{success: bool, message: string} 処理結果と表示メッセージ
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
     * 退勤打刻処理
     * 実労働時間、残業時間、深夜時間を計算して記録
     *
     * @param int $userId ユーザーID
     * @return array{success: bool, message: string} 処理結果と表示メッセージ
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

        // 勤務時間の計算（分）
        $workMinutes = $clockIn->floatDiffInMinutes($clockOut);
        // 実労働時間（休憩時間を引く）
        $actualWorkMinutes = $workMinutes - $attendance->break_time;

        // 残業時間の計算（分）
        $overtime = max(0, $actualWorkMinutes - self::REGULAR_WORK_MINUTES);

        // 深夜時間の計算
        $nightWorkMinutes = $this->calculateNightWorkMinutes($clockIn, $clockOut);

        $attendance->update([
            'clock_out' => $clockOut->format('H:i:s'),
            'actual_work_time' => (int)$actualWorkMinutes,
            'overtime' => $overtime > 0 ? (int)$overtime : null,
            'night_work_time' => $nightWorkMinutes > 0 ? (int)$nightWorkMinutes : null,
            'status' => 'left',
        ]);

        return [
            'success' => true,
            'message' => '退勤を記録しました。'
        ];
    }

    /**
     * 深夜時間を計算（22時〜5時の間の勤務時間）
     *
     * @param Carbon $clockIn 出勤時刻
     * @param Carbon $clockOut 退勤時刻
     * @return float 深夜時間（分）
     */
    private function calculateNightWorkMinutes(Carbon $clockIn, Carbon $clockOut): float
    {
        $nightWorkMinutes = 0;
        $currentTime = $clockIn->copy();

        while ($currentTime < $clockOut) {
            $hour = (int)$currentTime->format('H');

            // 22時〜24時、または0時〜5時の場合は深夜時間として計算
            if ($hour >= self::NIGHT_WORK_START || $hour < self::NIGHT_WORK_END) {
                $nightWorkMinutes++;
            }

            $currentTime->addMinute();
        }

        return $nightWorkMinutes;
    }

    /**
     * 当日の勤怠記録を取得
     *
     * @param int $userId ユーザーID
     * @return Attendance|null 勤怠記録
     */
    private function getTodayAttendance(int $userId): ?Attendance
    {
        return Attendance::where('user_id', $userId)
            ->where('date', Carbon::now()->toDateString())
            ->first();
    }

    /**
     * 出勤打刻が可能か判定
     *
     * @param Attendance|null $attendance 勤怠記録
     * @return bool 出勤打刻可能な場合はtrue
     */
    private function canClockIn(?Attendance $attendance): bool
    {
        return !$attendance;
    }

    /**
     * 退勤打刻が可能か判定
     *
     * @param Attendance|null $attendance 勤怠記録
     * @return bool 退勤打刻可能な場合はtrue
     */
    private function canClockOut(?Attendance $attendance): bool
    {
        return $attendance && $attendance->status === 'working';
    }

    /**
     * 当日の勤怠記録が存在するか確認
     *
     * @param int $userId ユーザーID
     * @return bool 勤怠記録が存在する場合はtrue
     */
    private function hasTodayAttendance(int $userId): bool
    {
        return Attendance::where('user_id', $userId)
            ->where('date', Carbon::now()->toDateString())
            ->exists();
    }

    /**
     * 当日の作業中の勤怠記録を取得
     *
     * @param int $userId ユーザーID
     * @return Attendance|null 勤怠記録
     */
    private function getTodayWorkingAttendance(int $userId): ?Attendance
    {
        return Attendance::where('user_id', $userId)
            ->where('date', Carbon::now()->toDateString())
            ->where('status', 'working')
            ->first();
    }

    /**
     * 勤怠データを表示用にフォーマット
     *
     * @param Attendance|null $attendance 勤怠記録
     * @return array 表示用にフォーマットされた勤怠データ
     */
    private function formatAttendanceData(?Attendance $attendance): array
    {
        if (!$attendance) {
            return [];
        }

        return [
            'clockInTime' => $attendance->clock_in
                ? Carbon::parse($attendance->clock_in)->format('H:i')
                : '未打刻',
            'clockOutTime' => $attendance->clock_out
                ? Carbon::parse($attendance->clock_out)->format('H:i')
                : '未打刻',
            'workTime' => TimeFormatter::minutesToTime($attendance->actual_work_time),
            'overtime' => TimeFormatter::minutesToTime($attendance->overtime),
            'nightWorkTime' => TimeFormatter::minutesToTime($attendance->night_work_time),
        ];
    }
}
