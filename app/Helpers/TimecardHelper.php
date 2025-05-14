<?php

namespace App\Helpers;

use App\Models\Timecard;
use App\Helpers\TimeHelper;
use Illuminate\Support\Carbon;

class TimecardHelper
{
    /**
     * 勤怠データを表示用にフォーマット
     */
    public static function formatTimecardDisplay(?Timecard $timecard = null): array
    {
        if (!$timecard) {
            $date = now();
            return [
                'date' => $date->locale('ja')->isoFormat('M月D日（dd）'),
                'clock_in' => '--:--',
                'clock_out' => '--:--',
                'break_time' => '--:--',
                'work_time' => '--:--',
                'overtime' => '--:--',
                'night_work' => '--:--',
                'status' => '未打刻',
                'day_class' => $date->dayOfWeek === 0 ? 'bg-weekend-sun' :
                             ($date->dayOfWeek === 6 ? 'bg-weekend-sat' : ''),
            ];
        }

        return [
            'id' => $timecard->id,
            'overtime' => TimeHelper::formatMinutesToTime($timecard->overtime_minutes),
            'night_work' => TimeHelper::formatMinutesToTime($timecard->night_minutes),
            'clock_in' => TimeHelper::formatDateTimeToTime($timecard->clock_in),
            'clock_out' => TimeHelper::formatDateTimeToTime($timecard->clock_out),
            'break_time' => TimeHelper::formatMinutesToTime(
                $timecard->break_start && $timecard->break_end
                ? $timecard->break_start->diffInMinutes($timecard->break_end)
                : 0
            ),
            'work_time' => TimeHelper::formatMinutesToTime(TimeHelper::calculateWorkMinutes($timecard)),
            'date' => $timecard->date->locale('ja')->isoFormat('M月D日（dd）'),
            'day_class' => $timecard->date->dayOfWeek === 0 ? 'bg-weekend-sun' :
                         ($timecard->date->dayOfWeek === 6 ? 'bg-weekend-sat' : ''),
            'status' => self::getStatusLabel($timecard)
        ];
    }

    /**
     * 勤怠ステータスラベルを取得
     */
    private static function getStatusLabel(Timecard $timecard): string
    {
        if ($timecard->clock_out) {
            return '退勤済み';
        }
        if ($timecard->break_start && !$timecard->break_end) {
            return '休憩中';
        }
        if ($timecard->clock_in) {
            return '勤務中';
        }
        return '未打刻';
    }

    public static function formatForDisplay(Timecard $timecard): array
    {
        return [
            'clock_in' => TimeHelper::formatDateTimeToTime($timecard->clock_in),
            'clock_out' => TimeHelper::formatDateTimeToTime($timecard->clock_out),
            'break_start' => TimeHelper::formatDateTimeToTime($timecard->break_start),
            'break_end' => TimeHelper::formatDateTimeToTime($timecard->break_end),
            'date_formatted' => DateHelper::formatJapaneseDateWithoutYear($timecard->date),
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
            'date_formatted' => DateHelper::formatJapaneseDateWithoutYear($timecard->date),
            'date_iso' => $timecard->date->format('Y-m-d')
        ];
    }
}
