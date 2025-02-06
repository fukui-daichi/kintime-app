<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Helpers\TimeFormatter;
use App\Constants\WorkTimeConstants;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * 勤怠管理の基本機能を担当するサービスクラス
 */
class AttendanceService
{
    private $monthlyService;

    public function __construct(MonthlyAttendanceService $monthlyService)
    {
        $this->monthlyService = $monthlyService;
    }

    /**
     * 月別勤怠一覧画面用のデータを取得
     *
     * @param int $userId ユーザーID
     * @param string|null $year 年（nullの場合は現在年）
     * @param string|null $month 月（nullの場合は現在月）
     * @return array 月別勤怠データ
     */
    public function getMonthlyAttendanceData(int $userId, ?string $year = null, ?string $month = null): array
    {
        return $this->monthlyService->getData($userId, $year, $month);
    }

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
    public function getDailyAttendanceData(int $userId): array
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
     * @return array{success: bool, message: string}
     */
    public function clockIn(int $userId): array
    {
        if ($this->hasTodayAttendance($userId)) {
            return $this->createResponse(false, '本日はすでに出勤打刻されています。');
        }

        try {
            $this->createAttendanceRecord($userId);
            return $this->createResponse(true, '出勤を記録しました。');
        } catch (\Exception $e) {
            Log::error('出勤打刻エラー', ['error' => $e->getMessage(), 'user_id' => $userId]);
            return $this->createResponse(false, '出勤打刻に失敗しました。');
        }
    }

    /**
     * 退勤打刻処理
     *
     * @param int $userId ユーザーID
     * @return array{success: bool, message: string}
     */
    public function clockOut(int $userId): array
    {
        $attendance = $this->getTodayWorkingAttendance($userId);

        if (!$attendance) {
            return $this->createResponse(false, '本日の出勤記録が見つかりません。');
        }

        try {
            $this->updateAttendanceForClockOut($attendance);
            return $this->createResponse(true, '退勤を記録しました。');
        } catch (\Exception $e) {
            Log::error('退勤打刻エラー', ['error' => $e->getMessage(), 'user_id' => $userId]);
            return $this->createResponse(false, '退勤打刻に失敗しました。');
        }
    }

    /**
     * 出勤記録を作成
     *
     * @param int $userId
     * @return Attendance
     */
    private function createAttendanceRecord(int $userId): Attendance
    {
        $now = Carbon::now();

        return Attendance::create([
            'user_id' => $userId,
            'date' => $now->toDateString(),
            'clock_in' => $now->toTimeString(),
            'break_time' => WorkTimeConstants::DEFAULT_BREAK_MINUTES,
            'status' => 'working',
        ]);
    }

    /**
     * 退勤時の勤怠記録更新
     *
     * @param Attendance $attendance
     * @return bool
     */
    private function updateAttendanceForClockOut(Attendance $attendance): bool
    {
        $clockIn = Carbon::parse($attendance->clock_in);
        $clockOut = Carbon::now();

        $workTimes = $this->calculateWorkTimes($clockIn, $clockOut, $attendance->break_time);

        return $attendance->update(array_merge(
            $workTimes,
            [
                'clock_out' => TimeFormatter::formatTime($clockOut),
                'status' => 'left',
            ]
        ));
    }

    /**
     * 勤務時間を計算
     *
     * @param Carbon $clockIn
     * @param Carbon $clockOut
     * @param int $breakTime
     * @return array
     */
    private function calculateWorkTimes(Carbon $clockIn, Carbon $clockOut, int $breakTime): array
    {
        try {
            $workMinutes = $clockIn->diffInMinutes($clockOut);
            $actualWorkMinutes = $workMinutes - $breakTime;
            $overtimeMinutes = max(0, $actualWorkMinutes - WorkTimeConstants::REGULAR_WORK_MINUTES);

            $this->logWorkTimeCalculation($clockIn, $clockOut, $workMinutes, $breakTime, $actualWorkMinutes);

            return [
                'actual_work_time' => $actualWorkMinutes,
                'overtime' => $overtimeMinutes,
                'night_work_time' => $this->calculateNightWorkMinutes($clockIn, $clockOut),
            ];
        } catch (\Exception $e) {
            Log::error('勤務時間計算エラー', [
                'error' => $e->getMessage(),
                'clock_in' => $clockIn->format('H:i'),
                'clock_out' => $clockOut->format('H:i'),
                'break_time' => $breakTime
            ]);
            throw $e;
        }
    }

    /**
     * 深夜時間を計算（22時〜5時の間の勤務時間）
     *
     * @param Carbon $clockIn
     * @param Carbon $clockOut
     * @return int 深夜時間（分）
     */
    private function calculateNightWorkMinutes(Carbon $clockIn, Carbon $clockOut): int
    {
        $nightWorkMinutes = 0;
        $currentTime = $clockIn->copy();
        $endTime = $clockOut->copy();

        if ($clockOut->lt($clockIn)) {
            $endTime->addDay();
        }

        while ($currentTime->lt($endTime)) {
            if ($this->isNightWorkHour((int)$currentTime->format('H'))) {
                $nightWorkMinutes++;
            }
            $currentTime->addMinute();
        }

        return $nightWorkMinutes;
    }

    /**
     * 指定時刻が深夜時間帯かどうかを判定
     *
     * @param int $hour
     * @return bool
     */
    private function isNightWorkHour(int $hour): bool
    {
        return $hour >= WorkTimeConstants::NIGHT_WORK_START_HOUR ||
               $hour < WorkTimeConstants::NIGHT_WORK_END_HOUR;
    }

    /**
     * 当日の勤怠記録を取得
     *
     * @param int $userId
     * @return Attendance|null
     */
    private function getTodayAttendance(int $userId): ?Attendance
    {
        return Attendance::where('user_id', $userId)
            ->where('date', Carbon::now()->toDateString())
            ->first();
    }

    /**
     * 当日の作業中の勤怠記録を取得
     *
     * @param int $userId
     * @return Attendance|null
     */
    private function getTodayWorkingAttendance(int $userId): ?Attendance
    {
        return Attendance::where('user_id', $userId)
            ->where('date', Carbon::now()->toDateString())
            ->where('status', 'working')
            ->first();
    }

    /**
     * 出勤打刻が可能か判定
     *
     * @param Attendance|null $attendance
     * @return bool
     */
    private function canClockIn(?Attendance $attendance): bool
    {
        return !$attendance;
    }

    /**
     * 退勤打刻が可能か判定
     *
     * @param Attendance|null $attendance
     * @return bool
     */
    private function canClockOut(?Attendance $attendance): bool
    {
        return $attendance && $attendance->status === 'working';
    }

    /**
     * 当日の勤怠記録が存在するか確認
     *
     * @param int $userId
     * @return bool
     */
    private function hasTodayAttendance(int $userId): bool
    {
        return Attendance::where('user_id', $userId)
            ->where('date', Carbon::now()->toDateString())
            ->exists();
    }

    /**
     * 勤怠データを表示用にフォーマット
     *
     * @param Attendance|null $attendance
     * @return array
     */
    private function formatAttendanceData(?Attendance $attendance): array
    {
        if (!$attendance) {
            return [];
        }

        return [
            'clockInTime' => TimeFormatter::formatTime(Carbon::parse($attendance->clock_in)),
            'clockOutTime' => TimeFormatter::formatTime(Carbon::parse($attendance->clock_out)),
            'workTime' => TimeFormatter::minutesToTime($attendance->actual_work_time),
            'overtime' => TimeFormatter::minutesToTime($attendance->overtime),
            'nightWorkTime' => TimeFormatter::minutesToTime($attendance->night_work_time),
        ];
    }

    /**
     * レスポンスデータを作成
     *
     * @param bool $success
     * @param string $message
     * @return array
     */
    private function createResponse(bool $success, string $message): array
    {
        return [
            'success' => $success,
            'message' => $message
        ];
    }

    /**
     * 勤務時間計算のログを記録
     *
     * @param Carbon $clockIn
     * @param Carbon $clockOut
     * @param int $workMinutes
     * @param int $breakTime
     * @param int $actualWorkMinutes
     */
    private function logWorkTimeCalculation(
        Carbon $clockIn,
        Carbon $clockOut,
        int $workMinutes,
        int $breakTime,
        int $actualWorkMinutes
    ): void {
        Log::debug('勤務時間計算詳細', [
            'clock_in' => $clockIn->format('H:i'),
            'clock_out' => $clockOut->format('H:i'),
            'total_minutes' => $workMinutes,
            'break_time' => $breakTime,
            'actual_minutes' => $actualWorkMinutes,
        ]);
    }
}
