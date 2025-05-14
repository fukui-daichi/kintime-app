<?php

namespace App\Helpers;

use App\Models\Timecard;
use App\Constants\WorkTimeConstants;
use Carbon\Carbon;

class TimeCalculator
{
    public static function overtime(Timecard $timecard): array
    {
        $totalMinutes = $timecard->clock_in->diffInMinutes($timecard->clock_out);

        $breakMinutes = 0;
        if ($timecard->break_start && $timecard->break_end) {
            $breakMinutes = $timecard->break_start->diffInMinutes($timecard->break_end);
        }

        $overtimeMinutes = $totalMinutes - $breakMinutes - (WorkTimeConstants::DEFAULT_WORK_HOURS * 60);
        $nightMinutes = self::nightTime($timecard);

        return [
            'overtime' => max($overtimeMinutes, 0),
            'night' => $nightMinutes
        ];
    }

    public static function nightTime(Timecard $timecard): int
    {
        $nightStart = Carbon::parse($timecard->date)->setHour(WorkTimeConstants::NIGHT_START_HOUR);
        $nightEnd = Carbon::parse($timecard->date)->addDay()->setHour(WorkTimeConstants::NIGHT_END_HOUR);

        $clockIn = Carbon::parse($timecard->clock_in);
        $clockOut = Carbon::parse($timecard->clock_out);

        $nightStart = max($nightStart, $clockIn);
        $nightEnd = min($nightEnd, $clockOut);

        $nightMinutes = $nightStart < $nightEnd ? $nightStart->diffInMinutes($nightEnd) : 0;

        if ($timecard->break_start && $timecard->break_end) {
            $breakStart = Carbon::parse($timecard->break_start);
            $breakEnd = Carbon::parse($timecard->break_end);

            $breakNightStart = max($nightStart, $breakStart);
            $breakNightEnd = min($nightEnd, $breakEnd);

            if ($breakNightStart < $breakNightEnd) {
                $nightMinutes -= $breakNightStart->diffInMinutes($breakNightEnd);
            }
        }

        return max($nightMinutes, 0);
    }

    public static function monthlyTotals(array $timecards): array
    {
        $totals = [
            'days_worked' => 0,
            'total_work' => 0,
            'total_overtime' => 0,
            'total_night' => 0
        ];

        foreach ($timecards as $tc) {
            if ($tc['clock_in'] !== '--:--') {
                $totals['days_worked']++;
            }
            $totals['total_work'] += TimeFormat::stringToMinutes($tc['work_time']);
            $totals['total_overtime'] += TimeFormat::stringToMinutes($tc['overtime']);
            $totals['total_night'] += TimeFormat::stringToMinutes($tc['night_work']);
        }

        return [
            'days_worked' => $totals['days_worked'],
            'total_work' => TimeFormat::minutesToHHMM($totals['total_work']),
            'total_overtime' => TimeFormat::minutesToHHMM($totals['total_overtime']),
            'total_night' => TimeFormat::minutesToHHMM($totals['total_night'])
        ];
    }
}
