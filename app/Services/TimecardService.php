<?php

namespace App\Services;

use App\Models\Timecard;
use App\Models\User;
use App\Repositories\TimecardRepository;
use App\Constants\WorkTimeConstants;
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
}
