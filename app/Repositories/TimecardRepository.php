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
            'clock_in' => Carbon::now()
        ]);
    }

    public function createClockOutRecord(int $userId): Timecard
    {
        return Timecard::where('user_id', $userId)
            ->whereNull('clock_out')
            ->latest()
            ->firstOrFail()
            ->update(['clock_out' => Carbon::now()]);
    }

    public function createBreakStartRecord(int $userId): Timecard
    {
        return Timecard::where('user_id', $userId)
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->latest()
            ->firstOrFail()
            ->update(['break_start' => Carbon::now()]);
    }

    public function createBreakEndRecord(int $userId): Timecard
    {
        return Timecard::where('user_id', $userId)
            ->whereNotNull('break_start')
            ->whereNull('break_end')
            ->latest()
            ->firstOrFail()
            ->update(['break_end' => Carbon::now()]);
    }

    public function getLatestTimecard(int $userId): ?Timecard
    {
        return Timecard::where('user_id', $userId)
            ->latest()
            ->first();
    }
}
