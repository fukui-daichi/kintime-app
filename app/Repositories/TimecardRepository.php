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

    /**
     * ユーザーIDと期間で勤怠データを取得
     */
    public function getByUserIdAndPeriod(int $userId, ?Carbon $startDate = null, ?Carbon $endDate = null)
    {
        $query = Timecard::where('user_id', $userId)
            ->orderBy('date', 'desc');

        if ($startDate) {
            $query->whereDate('date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('date', '<=', $endDate);
        }

        return $query->paginate(20);
    }

    /**
     * ユーザーIDと年月で勤怠データを取得（月内全日分）
     */
    public function getByUserIdAndMonth(int $userId, int $year, int $month)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        return Timecard::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->get();
    }

    /**
     * ユーザーの勤怠データが存在する年を取得
     */
    public function getAvailableYears(int $userId): array
    {
        return Timecard::where('user_id', $userId)
            ->selectRaw('YEAR(date) as year')
            ->groupBy('year')
            ->orderBy('year')
            ->pluck('year')
            ->toArray();
    }

    /**
     * タイムカード情報を更新
     */
    public function updateTimecard(Timecard $timecard, array $data): Timecard
    {
        $timecard->update([
            'clock_in' => $data['clock_in'],
            'clock_out' => $data['clock_out'],
            'break_start' => $data['break_start'],
            'break_end' => $data['break_end']
        ]);
        return $timecard->fresh();
    }
}
