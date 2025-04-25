<?php

namespace App\Services;

use App\Models\Timecard;
use App\Models\User;
use Illuminate\Support\Carbon;

class TimecardService
{
    /**
     * 出勤打刻
     */
    public function clockIn(User $user): Timecard
    {
        $today = Carbon::today();

        // 既に出勤打刻済みか確認
        $existing = Timecard::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if ($existing) {
            throw new \Exception('既に出勤打刻済みです');
        }

        return Timecard::create([
            'user_id' => $user->id,
            'date' => $today,
            'clock_in' => Carbon::now(),
            'status' => 'pending'
        ]);
    }

    /**
     * 退勤打刻
     */
    public function clockOut(User $user): Timecard
    {
        $today = Carbon::today();

        $timecard = Timecard::where('user_id', $user->id)
            ->where('date', $today)
            ->firstOrFail();

        if ($timecard->clock_out) {
            throw new \Exception('既に退勤打刻済みです');
        }

        $timecard->update([
            'clock_out' => Carbon::now()
        ]);

        return $timecard;
    }

    /**
     * 休憩開始
     */
    public function startBreak(User $user): Timecard
    {
        $today = Carbon::today();

        $timecard = Timecard::where('user_id', $user->id)
            ->where('date', $today)
            ->firstOrFail();

        if ($timecard->break_start) {
            throw new \Exception('既に休憩開始打刻済みです');
        }

        $timecard->update([
            'break_start' => Carbon::now()
        ]);

        return $timecard;
    }

    /**
     * 休憩終了
     */
    public function endBreak(User $user): Timecard
    {
        $today = Carbon::today();

        $timecard = Timecard::where('user_id', $user->id)
            ->where('date', $today)
            ->firstOrFail();

        if (!$timecard->break_start) {
            throw new \Exception('休憩開始打刻がありません');
        }

        if ($timecard->break_end) {
            throw new \Exception('既に休憩終了打刻済みです');
        }

        $timecard->update([
            'break_end' => Carbon::now()
        ]);

        return $timecard;
    }
}
