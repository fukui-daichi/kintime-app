<?php

namespace App\Services;

use App\Models\Timecard;
use App\Models\User;
use App\Repositories\TimecardRepository;
use Illuminate\Support\Carbon;

class TimecardService
{
    protected $repository;

    public function __construct(TimecardRepository $repository)
    {
        $this->repository = $repository;
    }
    /**
     * 出勤打刻
     */
    public function clockIn(User $user): Timecard
    {
        $today = Carbon::today();

        // 既に出勤打刻済みか確認
        $existing = $this->repository->getLatestTimecard($user->id);

        if ($existing && $existing->date->isToday()) {
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

        return $this->repository->createClockOutRecord($user->id);
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
}
