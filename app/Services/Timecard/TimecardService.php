<?php

namespace App\Services\Timecard;

use App\Models\Timecard;
use App\Helpers\TimeFormatter;
use App\Constants\WorkTimeConstants;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * 勤怠管理の基本機能を担当するサービスクラス
 */
class TimecardService
{
    private $monthlyService;

    public function __construct(MonthlyTimecardService $monthlyService)
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
    public function getMonthlyTimecardData(int $userId, ?string $year = null, ?string $month = null): array
    {
        return $this->monthlyService->getData($userId, $year, $month);
    }

    /**
     * 勤怠情報に関するデータを取得
     *
     * @param int $userId ユーザーID
     * @return array{
     *   timecard: ?Timecard,
     *   canClockIn: bool,
     *   canClockOut: bool,
     *   timecardData: array
     * }
     */
    public function getDailyTimecardData(int $userId): array
    {
        $timecard = $this->getTodayTimecard($userId);

        return [
            'timecard' => $timecard,
            'canClockIn' => $this->canClockIn($timecard),
            'canClockOut' => $this->canClockOut($timecard),
            'timecardData' => $this->formatTimecardData($timecard),
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
        if ($this->hasTodayTimecard($userId)) {
            return $this->createResponse(false, '本日はすでに出勤打刻されています。');
        }

        try {
            $this->createTimecardRecord($userId);
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
        $timecard = $this->getTodayWorkingTimecard($userId);

        if (!$timecard) {
            return $this->createResponse(false, '本日の出勤記録が見つかりません。');
        }

        try {
            $this->updateTimecardForClockOut($timecard);
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
     * @return Timecard
     */
    private function createTimecardRecord(int $userId): Timecard
    {
        $now = Carbon::now();

        return Timecard::create([
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
     * @param Timecard $timecard
     * @return bool
     */
    private function updateTimecardForClockOut(Timecard $timecard): bool
    {
        $clockIn = Carbon::parse($timecard->clock_in);
        $clockOut = Carbon::now();

        $workTimes = $this->calculateWorkTimes($clockIn, $clockOut, $timecard->break_time);

        return $timecard->update(array_merge(
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
     * @return Timecard|null
     */
    private function getTodayTimecard(int $userId): ?Timecard
    {
        return Timecard::where('user_id', $userId)
            ->where('date', Carbon::now()->toDateString())
            ->first();
    }

    /**
     * 当日の作業中の勤怠記録を取得
     *
     * @param int $userId
     * @return Timecard|null
     */
    private function getTodayWorkingTimecard(int $userId): ?Timecard
    {
        return Timecard::where('user_id', $userId)
            ->where('date', Carbon::now()->toDateString())
            ->where('status', 'working')
            ->first();
    }

    /**
     * 出勤打刻が可能か判定
     *
     * @param Timecard|null $timecard
     * @return bool
     */
    private function canClockIn(?Timecard $timecard): bool
    {
        return !$timecard;
    }

    /**
     * 退勤打刻が可能か判定
     *
     * @param Timecard|null $timecard
     * @return bool
     */
    private function canClockOut(?Timecard $timecard): bool
    {
        return $timecard && $timecard->status === 'working';
    }

    /**
     * 当日の勤怠記録が存在するか確認
     *
     * @param int $userId
     * @return bool
     */
    private function hasTodayTimecard(int $userId): bool
    {
        return Timecard::where('user_id', $userId)
            ->where('date', Carbon::now()->toDateString())
            ->exists();
    }

    /**
     * 勤怠データを表示用にフォーマット
     *
     * @param Timecard|null $timecard 勤怠データ
     * @return array フォーマット済みの勤怠データ
     */
    private function formatTimecardData(?Timecard $timecard): array
    {
        if (!$timecard) {
            return [];
        }

        return [
            'clockInTime' => $timecard->clock_in
                ? TimeFormatter::formatTime(Carbon::parse($timecard->clock_in))
                : null,
            'clockOutTime' => $timecard->clock_out
                ? TimeFormatter::formatTime(Carbon::parse($timecard->clock_out))
                : null,
            'workTime' => $timecard->actual_work_time
                ? TimeFormatter::minutesToTime($timecard->actual_work_time)
                : null,
            'overtime' => $timecard->overtime
                ? TimeFormatter::minutesToTime($timecard->overtime)
                : null,
            'nightWorkTime' => $timecard->night_work_time
                ? TimeFormatter::minutesToTime($timecard->night_work_time)
                : null,
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
