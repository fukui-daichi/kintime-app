<?php

namespace App\Services\Attendance;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\Helpers\TimeFormatter;

class AttendanceDataFormatter
{
    /**
     * 月の勤怠データを日付ごとに整形
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param Collection $attendances
     * @return Collection
     */
    public function formatMonthlyData(Carbon $startDate, Carbon $endDate, Collection $attendances): Collection
    {
        $result = collect();
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            // dateカラムの値をY-m-d形式に変換して比較
            $attendance = $attendances->first(function ($attendance) use ($currentDate) {
                return Carbon::parse($attendance->date)->format('Y-m-d') === $currentDate->format('Y-m-d');
            });

            $result->push([
                'date' => $currentDate->copy(),
                'attendance' => $attendance,
                'is_weekend' => $currentDate->isWeekend(),
                'clock_in' => $attendance?->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : null,
                'clock_out' => $attendance?->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : null,
                'work_time' => TimeFormatter::minutesToTime($attendance?->actual_work_time),
                'overtime' => TimeFormatter::minutesToTime($attendance?->overtime),
                'night_work_time' => TimeFormatter::minutesToTime($attendance?->night_work_time),
            ]);

            $currentDate->addDay();
        }

        return $result;
    }
}
