<?php

namespace App\Helpers;

class TimeFormatter
{
    /**
     * 分を時間表示形式（H:mm）に変換
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
}
