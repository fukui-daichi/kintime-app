<?php

namespace App\Helpers;

class TimeHelper
{
    /**
     * 分数をHH:MM形式に変換
     */
    public static function formatMinutesToTime(int $minutes): string
    {
        $hours = floor($minutes / 60);
        $minutes = $minutes % 60;
        return sprintf('%02d:%02d', $hours, $minutes);
    }

    /**
     * HH:MM形式を分数に変換
     */
    public static function timeToMinutes(string $time): int
    {
        if ($time === '--:--' || $time === '00:00') {
            return 0;
        }
        list($hours, $minutes) = explode(':', $time);
        return (int)$hours * 60 + (int)$minutes;
    }

    /**
     * 指定年月の日付リスト（1日〜末日）を返す
     * @return array ['04-01', '04-02', ...]
     */
    public static function getMonthDateList(int $year, int $month): array
    {
        $days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $dates = [];
        for ($d = 1; $d <= $days; $d++) {
            $dates[] = sprintf('%02d-%02d', $month, $d);
        }
        return $dates;
    }

    /**
     * 日時を指定フォーマットで表示
     */
    public static function formatDateTime(\DateTime $datetime, string $format = 'H:i'): string
    {
        return $datetime->format($format);
    }
}
