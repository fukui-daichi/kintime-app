<?php

namespace App\Helpers;

use App\Models\Timecard;
use App\Constants\WorkTimeConstants;
use Carbon\Carbon;
use DateTime;

class TimeHelper
{
    // =============================================
    // 時間フォーマット変換処理
    // =============================================

    /**
     * 分数をHH:MM形式の文字列に変換
     */
    public static function formatMinutesToTime(int $minutes): string
    {
        $hours = floor($minutes / 60);
        $minutes = $minutes % 60;
        return sprintf('%02d:%02d', $hours, $minutes);
    }

    /**
     * HH:MM形式の文字列を分数に変換
     */
    public static function formatTimeToMinutes(string $time): int
    {
        if ($time === '--:--' || $time === '00:00') {
            return 0;
        }
        list($hours, $minutes) = explode(':', $time);
        return (int)$hours * 60 + (int)$minutes;
    }

    /**
     * DateTimeオブジェクトを分数に変換
     */
    public static function formatDateTimeToMinutes(\DateTime $time): int
    {
        return (int)$time->format('H') * 60 + (int)$time->format('i');
    }

    /**
     * DateTimeオブジェクトを指定フォーマットの文字列に変換 (nullの場合は'--:--')
     */
    public static function formatDateTimeToTime($datetime, string $format = 'H:i'): string
    {
        if ($datetime instanceof \DateTime) {
            return $datetime->format($format);
        }
        return $datetime ? date($format, strtotime($datetime)) : '--:--';
    }

    // =============================================
    // 時間計算処理
    // =============================================

    /**
     * 勤務時間を分数で計算
     */
    public static function calculateWorkMinutes(Timecard $timecard): int
    {
        if (!$timecard->clock_in || !$timecard->clock_out) {
            return 0;
        }

        $minutes = $timecard->clock_in->diffInMinutes($timecard->clock_out);

        if ($timecard->break_start && $timecard->break_end) {
            $minutes -= $timecard->break_start->diffInMinutes($timecard->break_end);
        }

        return $minutes;
    }

    /**
     * 残業時間と深夜時間を計算
     */
    public static function calculateOvertimeMinutes(Timecard $timecard): array
    {
        $totalMinutes = $timecard->clock_in->diffInMinutes($timecard->clock_out);

        $breakMinutes = 0;
        if ($timecard->break_start && $timecard->break_end) {
            $breakMinutes = $timecard->break_start->diffInMinutes($timecard->break_end);
        }

        $overtimeMinutes = $totalMinutes - $breakMinutes - (WorkTimeConstants::DEFAULT_WORK_HOURS * 60);
        $nightMinutes = self::calculateNightWorkMinutes($timecard);

        return [
            'overtime' => max($overtimeMinutes, 0),
            'night' => $nightMinutes
        ];
    }

    /**
     * 深夜勤務時間を計算
     */
    public static function calculateNightWorkMinutes(Timecard $timecard): int
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

    /**
     * 休憩時間を分数で計算
     */
    public static function calculateBreakMinutes(Timecard $timecard): int
    {
        return $timecard->break_start && $timecard->break_end
            ? $timecard->break_start->diffInMinutes($timecard->break_end)
            : 0;
    }

    /**
     * 月間合計を計算
     */
    public static function calculateMonthlyTotals(array $timecards): array
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
            $totals['total_work'] += self::formatTimeToMinutes($tc['work_time']);
            $totals['total_overtime'] += self::formatTimeToMinutes($tc['overtime']);
            $totals['total_night'] += self::formatTimeToMinutes($tc['night_work']);
        }

        return [
            'days_worked' => $totals['days_worked'],
            'total_work' => self::formatMinutesToTime($totals['total_work']),
            'total_overtime' => self::formatMinutesToTime($totals['total_overtime']),
            'total_night' => self::formatMinutesToTime($totals['total_night'])
        ];
    }
}
