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
     * 日時を指定フォーマットで表示
     */
    public static function formatDateTime(\DateTime $datetime, string $format = 'H:i'): string
    {
        return $datetime->format($format);
    }
}
