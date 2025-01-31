<?php

namespace App\Services;

use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceService
{
    /**
     * 勤怠情報に関するデータを取得
     *
     * @param int $userId ユーザーID
     * @return array 勤怠情報の配列
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
     * 本日の勤怠データを取得
     *
     * @param int $userId ユーザーID
     * @return Attendance|null
     */
    public function getTodayAttendance(int $userId)
    {
        return Attendance::where('user_id', $userId)
            ->where('date', Carbon::now()->toDateString())
            ->first();
    }

    /**
     * 出勤処理
     *
     * @param int $userId ユーザーID
     * @return array 処理結果の配列
     */
    public function clockIn(int $userId): array
    {
        $now = Carbon::now();

        // 同日の打刻チェック
        if ($this->hasTodayAttendance($userId)) {
            return [
                'success' => false,
                'message' => '本日はすでに出勤打刻されています。'
            ];
        }

        // 勤怠データの作成
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
     *
     * @param int $userId ユーザーID
     * @return array 処理結果の配列
     */
    public function clockOut(int $userId): array
    {
        $now = Carbon::now();
        $attendance = $this->getTodayWorkingAttendance($userId);

        // 出勤記録の確認
        if (!$attendance) {
            return [
                'success' => false,
                'message' => '本日の出勤記録が見つかりません。'
            ];
        }

        // 時刻を時間オブジェクトに変換
        $clockIn = Carbon::parse($attendance->clock_in);
        $clockOut = Carbon::now();

        // 実労働時間を計算（分単位）
        $workMinutes = -1 * $clockIn->floatDiffInMinutes($clockOut);

        // 休憩時間を引く
        $actualWorkMinutes = $workMinutes - $attendance->break_time;

        // 勤怠データの更新
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

    /**
     * 出勤打刻が可能か判定
     *
     * @param Attendance|null $attendance 勤怠データ
     * @return bool
     */
    private function canClockIn(?Attendance $attendance): bool
    {
        return !$attendance || $attendance->status !== 'working';
    }

    /**
     * 退勤打刻が可能か判定
     *
     * @param Attendance|null $attendance 勤怠データ
     * @return bool
     */
    private function canClockOut(?Attendance $attendance): bool
    {
        return $attendance && $attendance->status === 'working';
    }

    /**
     * 本日の勤怠データ存在チェック
     *
     * @param int $userId ユーザーID
     * @return bool
     */
    private function hasTodayAttendance(int $userId): bool
    {
        return Attendance::where('user_id', $userId)
            ->where('date', Carbon::now()->toDateString())
            ->exists();
    }

    /**
     * 本日の勤務中データ取得
     *
     * @param int $userId ユーザーID
     * @return Attendance|null
     */
    private function getTodayWorkingAttendance(int $userId)
    {
        return Attendance::where('user_id', $userId)
            ->where('date', Carbon::now()->toDateString())
            ->where('status', 'working')
            ->first();
    }

    /**
     * 勤怠データを表示用にフォーマット
     *
     * @param Attendance|null $attendance 勤怠データ
     * @return array
     */
    private function formatAttendanceData(?Attendance $attendance): array
    {
        if (!$attendance) {
            return [];
        }

        // 実労働時間を時間と分に分解
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
