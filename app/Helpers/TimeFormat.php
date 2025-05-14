<?php

namespace App\Helpers;

use App\Models\Timecard;
use Carbon\Carbon;
use DateTime;

class TimeFormat
{
    /**
     * 分数をHH:MM形式に変換
     */
    public static function minutesToHHMM(int $minutes): string
    {
        $hours = floor($minutes / 60);
        $minutes = $minutes % 60;
        return sprintf('%02d:%02d', $hours, $minutes);
    }

    /**
     * HH:MM形式の文字列を分数に変換
     */
    public static function stringToMinutes(string $time): int
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
    public static function dateTimeToMinutes(\DateTime $time): int
    {
        return (int)$time->format('H') * 60 + (int)$time->format('i');
    }

    /**
     * 時間をフォーマット (nullの場合は'--:--')
     */
    public static function dateTimeToHHMM(?\DateTime $time, string $format = 'H:i'): string
    {
        return $time ? $time->format($format) : '--:--';
    }

}
