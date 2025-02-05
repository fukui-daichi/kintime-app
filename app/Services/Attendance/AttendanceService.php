<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Helpers\TimeFormatter;
use App\Constants\WorkTimeConstants;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * 勤怠管理に関するサービスクラス
 * 月次データの取得、日次の打刻処理を担当
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
            return [
                'success' => false,
                'message' => '本日はすでに出勤打刻されています。'
            ];
        }

        $now = Carbon::now();

        Attendance::create([
            'user_id' => $userId,
            'date' => $now->toDateString(),
            'clock_in' => $now->toTimeString(),
            'break_time' => WorkTimeConstants::DEFAULT_BREAK_MINUTES,
            'status' => 'working',
        ]);

        return [
            'success' => true,
            'message' => '出勤を記録しました。'
        ];
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
            return [
                'success' => false,
                'message' => '本日の出勤記録が見つかりません。'
            ];
        }

        $clockIn = Carbon::parse($attendance->clock_in);
        $clockOut = Carbon::now();

        // 勤務時間を計算（分）
        $workMinutes = $clockIn->diffInMinutes($clockOut);
        $breakTime = $attendance->break_time ?? WorkTimeConstants::DEFAULT_BREAK_MINUTES;

        // 実労働時間（休憩時間を引く）
        $actualWorkMinutes = $workMinutes - $breakTime;

        // 残業時間の計算
        $overtimeMinutes = max(0, $actualWorkMinutes - WorkTimeConstants::REGULAR_WORK_MINUTES);

        // 深夜時間の計算
        $nightWorkMinutes = $this->calculateNightWorkMinutes($clockIn, $clockOut);

        $attendance->update([
            'clock_out' => TimeFormatter::formatTime($clockOut),
            'actual_work_time' => $actualWorkMinutes,
            'overtime' => $overtimeMinutes,
            'night_work_time' => $nightWorkMinutes,
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
     * @return int 深夜時間（分）
     */
    private function calculateNightWorkMinutes(Carbon $clockIn, Carbon $clockOut): int
    {
        $nightWorkMinutes = 0;
        $currentTime = $clockIn->copy();

        while ($currentTime < $clockOut) {
            $hour = (int)$currentTime->format('H');
            if ($hour >= WorkTimeConstants::NIGHT_WORK_START_HOUR ||
                $hour < WorkTimeConstants::NIGHT_WORK_END_HOUR) {
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
            'clockInTime' => TimeFormatter::formatTime(Carbon::parse($attendance->clock_in)),
            'clockOutTime' => TimeFormatter::formatTime(Carbon::parse($attendance->clock_out)),
            'workTime' => TimeFormatter::minutesToTime($attendance->actual_work_time),
            'overtime' => TimeFormatter::minutesToTime($attendance->overtime),
            'nightWorkTime' => TimeFormatter::minutesToTime($attendance->night_work_time),
        ];
    }
}
