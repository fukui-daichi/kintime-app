<?php

namespace App\Helpers;

use App\Models\Timecard;
use App\Helpers\TimeHelper;

class TimecardHelper
{
    public static function formatForDisplay(Timecard $timecard): array
    {
        return [
            'clock_in' => TimeHelper::formatTime($timecard->clock_in),
            'clock_out' => TimeHelper::formatTime($timecard->clock_out),
            'break_start' => TimeHelper::formatTime($timecard->break_start),
            'break_end' => TimeHelper::formatTime($timecard->break_end),
            'date_formatted' => TimeHelper::formatJapaneseDate($timecard->date),
            'date_iso' => $timecard->date->format('Y-m-d')
        ];
    }

    public static function formatForEdit(Timecard $timecard): array
    {
        return [
            'id' => $timecard->id,
            'clock_in' => $timecard->clock_in ? $timecard->clock_in->format('H:i') : null,
            'clock_out' => $timecard->clock_out ? $timecard->clock_out->format('H:i') : null,
            'break_start' => $timecard->break_start ? $timecard->break_start->format('H:i') : null,
            'break_end' => $timecard->break_end ? $timecard->break_end->format('H:i') : null,
            'date_formatted' => TimeHelper::formatJapaneseDate($timecard->date),
            'date_iso' => $timecard->date->format('Y-m-d')
        ];
    }
}
