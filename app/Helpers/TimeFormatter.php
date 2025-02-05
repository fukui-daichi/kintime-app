<?php

namespace App\Helpers;

class TimeFormatter
{
    /**
     * 分を時間表示形式（H:mm）に変換
     * 例: 90 → 1:30
     *
     * @param int|null $minutes
     * @return string|null
     */
    public static function minutesToTime(?int $minutes): ?string
    {
        if ($minutes === null) {
            return null;
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return sprintf('%d:%02d', $hours, $remainingMinutes);
    }

    /**
     * 時間表示形式（H:mm）を分に変換
     * 例: 1:30 → 90
     *
     * @param string|null $time
     * @return int|null
     */
    public static function timeToMinutes(?string $time): ?int
    {
        if (empty($time)) {
            return null;
        }

        [$hours, $minutes] = array_pad(explode(':', $time), 2, 0);
        return ($hours * 60) + $minutes;
    }
}
