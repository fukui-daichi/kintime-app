<?php

namespace App\Helpers;

class TimeHelper
{
    /**
     * 分数をHH:MM形式に変換
     */
    public static function formatMinutesToTime(int $minutes): string
    {
        return floor($minutes / 60) . ':' . str_pad($minutes % 60, 2, '0', STR_PAD_LEFT);
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
