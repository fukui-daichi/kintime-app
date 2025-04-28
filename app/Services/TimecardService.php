<?php

namespace App\Services;

use App\Models\Timecard;
use App\Models\User;
use App\Repositories\TimecardRepository;
use App\Constants\WorkTimeConstants;
use App\Helpers\TimeHelper;
use Illuminate\Support\Carbon;

class TimecardService
{
    protected $repository;

    public function __construct(TimecardRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getTodayTimecard(int $userId): ?Timecard
    {
        return $this->repository->getTodayTimecard($userId);
    }

    public function getTimecardButtonStatus(int $userId): array
    {
        $timecard = $this->repository->getTodayTimecard($userId);

        return [
            'clockIn' => [
                'disabled' => $timecard !== null && $timecard->clock_in !== null,
                'label' => '出勤打刻'
            ],
            'clockOut' => [
                'disabled' => $timecard === null || $timecard->clock_out !== null,
                'label' => '退勤打刻'
            ],
            'breakStart' => [
                'disabled' => $timecard === null || $timecard->clock_out !== null || $timecard->break_start !== null || ($timecard->break_start !== null && $timecard->break_end === null),
                'label' => '休憩開始'
            ],
            'breakEnd' => [
                'disabled' => $timecard === null || $timecard->clock_out !== null || $timecard->break_start === null || $timecard->break_end !== null || $timecard->break_start === null,
                'label' => '休憩終了'
            ]
        ];
    }

    /**
     * 出勤打刻
     */
    public function clockIn(User $user): Timecard
    {
        $today = Carbon::today();

        // 既に出勤打刻済みか確認
        $existing = $this->repository->getTodayTimecard($user->id);

        if ($existing && $existing->clock_in !== null) {
            throw new \Exception('既に出勤打刻済みです');
        }

        return $this->repository->createClockInRecord($user->id);
    }

    /**
     * 退勤打刻
     */
    public function clockOut(User $user): Timecard
    {
        $timecard = $this->repository->getLatestTimecard($user->id);

        if (!$timecard || $timecard->clock_out) {
            throw new \Exception('退勤打刻ができません');
        }

        $timecard = $this->repository->createClockOutRecord($user->id);
        $this->saveCalculatedTime($timecard);

        return $timecard;
    }

    /**
     * 休憩開始
     */
    public function startBreak(User $user): Timecard
    {
        $timecard = $this->repository->getLatestTimecard($user->id);

        if (!$timecard || $timecard->break_start) {
            throw new \Exception('休憩開始打刻ができません');
        }

        return $this->repository->createBreakStartRecord($user->id);
    }

    /**
     * 休憩終了
     */
    public function endBreak(User $user): Timecard
    {
        $timecard = $this->repository->getLatestTimecard($user->id);

        if (!$timecard || !$timecard->break_start || $timecard->break_end) {
            throw new \Exception('休憩終了打刻ができません');
        }

        return $this->repository->createBreakEndRecord($user->id);
    }

    /**
     * 残業時間と深夜時間を計算
     */
    public function calculateOvertime(Timecard $timecard): array
    {
        // 総勤務時間（分）
        $totalMinutes = $timecard->clock_in->diffInMinutes($timecard->clock_out);

        // 休憩時間（分）休憩開始・終了が両方記録されている場合のみ計算
        $breakMinutes = 0;
        if ($timecard->break_start && $timecard->break_end) {
            $breakMinutes = $timecard->break_start->diffInMinutes($timecard->break_end);
        }

        // 残業時間 = 総勤務時間 - 休憩時間 - 基本勤務時間（8時間×60分）
        $overtimeMinutes = $totalMinutes - $breakMinutes - (WorkTimeConstants::DEFAULT_WORK_HOURS * 60);
        $nightMinutes = $this->calculateNightTime($timecard);

        return [
            'overtime' => max($overtimeMinutes, 0),
            'night' => $nightMinutes
        ];
    }

    /**
     * 深夜時間を計算
     */
    private function calculateNightTime(Timecard $timecard): int
    {
        $nightStart = Carbon::parse($timecard->date)->setHour(WorkTimeConstants::NIGHT_START_HOUR);
        $nightEnd = Carbon::parse($timecard->date)->addDay()->setHour(WorkTimeConstants::NIGHT_END_HOUR);

        $clockIn = Carbon::parse($timecard->clock_in);
        $clockOut = Carbon::parse($timecard->clock_out);

        $nightStart = max($nightStart, $clockIn);
        $nightEnd = min($nightEnd, $clockOut);

        $nightMinutes = $nightStart < $nightEnd ? $nightStart->diffInMinutes($nightEnd) : 0;

        // 休憩時間が深夜時間帯と重なる場合、その分を差し引く
        if ($timecard->break_start && $timecard->break_end) {
            $breakStart = Carbon::parse($timecard->break_start);
            $breakEnd = Carbon::parse($timecard->break_end);

            $breakNightStart = max($nightStart, $breakStart);
            $breakNightEnd = min($nightEnd, $breakEnd);

            if ($breakNightStart < $breakNightEnd) {
                $nightMinutes -= $breakNightStart->diffInMinutes($breakNightEnd);
            }
        }

        return max($nightMinutes, 0); // 負の値にならないように
    }

    /**
     * 計算結果を保存
     */
    public function saveCalculatedTime(Timecard $timecard): void
    {
        $result = $this->calculateOvertime($timecard);
        $timecard->update([
            'overtime_minutes' => $result['overtime'],
            'night_minutes' => $result['night']
        ]);
    }

    /**
     * 表示用にタイムカードデータをフォーマット
     */
    public function formatTimecardForDisplay(Timecard $timecard): array
    {
        return [
            'overtime' => TimeHelper::formatMinutesToTime($timecard->overtime_minutes),
            'night_work' => TimeHelper::formatMinutesToTime($timecard->night_minutes),
            'clock_in' => $timecard->clock_in ? TimeHelper::formatDateTime($timecard->clock_in) : '--:--',
            'clock_out' => $timecard->clock_out ? TimeHelper::formatDateTime($timecard->clock_out) : '--:--',
            'break_time' => TimeHelper::formatMinutesToTime(
                $timecard->break_start && $timecard->break_end
                ? $timecard->break_start->diffInMinutes($timecard->break_end)
                : 0
            ),
            'work_time' => TimeHelper::formatMinutesToTime(
                $timecard->clock_in && $timecard->clock_out
                ? $timecard->clock_in->diffInMinutes($timecard->clock_out) -
                ($timecard->break_start && $timecard->break_end
                    ? $timecard->break_start->diffInMinutes($timecard->break_end)
                    : 0)
                : 0
            ),
            'date' => $timecard->date->format('m-d'),
            'status' => $this->getStatusLabel($timecard)
        ];
    }

    /**
     * ユーザーIDと期間で勤怠データを取得
     */
    public function getTimecardsByPeriod(int $userId, ?Carbon $startDate = null, ?Carbon $endDate = null)
    {
        $timecards = $this->repository->getByUserIdAndPeriod($userId, $startDate, $endDate);

        $timecards->getCollection()->transform(function ($timecard) {
            return $this->formatTimecardForDisplay($timecard);
        });

        return $timecards;
    }

    /**
     * ユーザーIDと年月で勤怠データを取得（月内全日分、空欄も含めて返す）
     */
    public function getTimecardsByMonth(int $userId, int $year, int $month)
    {
        $dateList = \App\Helpers\TimeHelper::getMonthDateList($year, $month);
        $timecards = $this->repository->getByUserIdAndMonth($userId, $year, $month)->keyBy(function ($tc) {
            return date('m-d', strtotime($tc->date));
        });

        $result = [];
        foreach ($dateList as $md) {
            if (isset($timecards[$md])) {
                $result[] = $this->formatTimecardForDisplay($timecards[$md]);
            } else {
                $result[] = [
                    'date' => $md,
                    'clock_in' => '--:--',
                    'clock_out' => '--:--',
                    'break_time' => '00:00',
                    'work_time' => '00:00',
                    'overtime' => '00:00',
                    'night_work' => '00:00',
                    'status' => '未打刻',
                ];
            }
        }
        return collect($result);
    }

    /**
     * 勤怠ステータスラベルを取得
     */
    private function getStatusLabel(Timecard $timecard): string
    {
        if ($timecard->clock_out) {
            return '退勤済み';
        }
        if ($timecard->break_start && !$timecard->break_end) {
            return '休憩中';
        }
        if ($timecard->clock_in) {
            return '勤務中';
        }
        return '未打刻';
    }

}
