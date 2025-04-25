<?php

namespace App\Repositories;

use App\Models\Timecard;
use Carbon\Carbon;

class TimecardRepository
{
    public function createClockInRecord(int $userId): Timecard
    {
        return Timecard::create([
            'user_id' => $userId,
            'date' => Carbon::today(),
            'clock_in' => Carbon::now()
        ]);
    }

    public function createClockOutRecord(int $userId): Timecard
    {
        $timecard = Timecard::where('user_id', $userId)
            ->whereNull('clock_out')
            ->latest()
            ->firstOrFail();

        $timecard->update(['clock_out' => Carbon::now()]);
        return $timecard->fresh();
    }

    public function createBreakStartRecord(int $userId): Timecard
    {
        $timecard = Timecard::where('user_id', $userId)
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->latest()
            ->firstOrFail();

        $timecard->update(['break_start' => Carbon::now()]);
        return $timecard->fresh();
    }

    public function createBreakEndRecord(int $userId): Timecard
    {
        $timecard = Timecard::where('user_id', $userId)
            ->whereNotNull('break_start')
            ->whereNull('break_end')
            ->latest()
            ->firstOrFail();

        $timecard->update(['break_end' => Carbon::now()]);
        return $timecard->fresh();
    }

    public function getLatestTimecard(int $userId): ?Timecard
    {
        return Timecard::where('user_id', $userId)
            ->latest()
            ->first();
    }

    public function getTodayTimecard(int $userId): ?Timecard
    {
        return Timecard::where('user_id', $userId)
            ->whereDate('date', Carbon::today())
            ->latest()
            ->first();
    }
}
