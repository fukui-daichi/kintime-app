<?php

namespace App\Services\Attendance;

use Carbon\Carbon;
use Illuminate\Support\Collection;

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
            $attendance = $attendances->firstWhere('date', $currentDate->toDateString());

            $result->push([
                'date' => $currentDate->copy(),
                'attendance' => $attendance,
                'is_weekend' => $currentDate->isWeekend(),
                'clock_in' => $attendance ? Carbon::parse($attendance->clock_in)->format('H:i') : null,
                'clock_out' => $attendance?->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : null,
                'work_hours' => $attendance?->actual_work_time ? floor($attendance->actual_work_time / 60) : null,
                'work_minutes' => $attendance?->actual_work_time ? $attendance->actual_work_time % 60 : null,
            ]);

            $currentDate->addDay();
        }

        return $result;
    }
}
