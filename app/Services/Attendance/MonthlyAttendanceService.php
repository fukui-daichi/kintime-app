<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Helpers\TimeFormatter;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * 月次勤怠データの取得と加工を担当するサービスクラス
 */
class MonthlyAttendanceService
{
    /**
     * 月別勤怠一覧画面用のデータを取得
     *
     * @param int $userId ユーザーID
     * @param string|null $year 年（nullの場合は現在年）
     * @param string|null $month 月（nullの場合は現在月）
     * @return array{
     *   attendances: Collection,
     *   targetDate: Carbon,
     *   previousMonth: Carbon,
     *   nextMonth: Carbon,
     *   showNextMonth: bool,
     *   years: array,
     *   months: array
     * }
     */
    public function getData(int $userId, ?string $year = null, ?string $month = null): array
    {
        $targetDate = $this->createTargetDate($year, $month);
        $currentDate = now()->startOfMonth();

        return [
            'attendances' => $this->getMonthlyAttendance($userId, $targetDate),
            'targetDate' => $targetDate,
            'previousMonth' => $targetDate->copy()->subMonth(),
            'nextMonth' => $targetDate->copy()->addMonth(),
            'showNextMonth' => $targetDate->copy()->addMonth()->lte($currentDate),
            'years' => $this->generateYearOptions(),
            'months' => $this->generateMonthOptions($targetDate->year),
        ];
    }

    /**
     * 対象月の勤怠データを取得して整形
     *
     * @param int $userId ユーザーID
     * @param Carbon $targetDate 対象年月
     * @return Collection
     */
    private function getMonthlyAttendance(int $userId, Carbon $targetDate): Collection
    {
        // 月の範囲を設定
        $dateRange = $this->getMonthDateRange($targetDate);

        // DBから勤怠データを取得
        $attendances = $this->fetchAttendanceData($userId, $dateRange);

        // カレンダーデータを生成
        return $this->generateCalendarData($dateRange['start'], $dateRange['end'], $attendances);
    }

    /**
     * 月の開始日と終了日を取得
     *
     * @param Carbon $targetDate
     * @return array{start: Carbon, end: Carbon}
     */
    private function getMonthDateRange(Carbon $targetDate): array
    {
        return [
            'start' => $targetDate->copy()->startOfMonth(),
            'end' => $targetDate->copy()->endOfMonth()
        ];
    }

    /**
     * DBから勤怠データを取得
     *
     * @param int $userId
     * @param array{start: Carbon, end: Carbon} $dateRange
     * @return Collection
     */
    private function fetchAttendanceData(int $userId, array $dateRange): Collection
    {
        return Attendance::where('user_id', $userId)
            ->whereBetween('date', [
                $dateRange['start']->toDateString(),
                $dateRange['end']->toDateString()
            ])
            ->orderBy('date')
            ->get()
            ->keyBy(fn($attendance) => $attendance->date->format('Y-m-d'));
    }

    /**
     * カレンダーデータを生成
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param Collection $attendances
     * @return Collection
     */
    private function generateCalendarData(
        Carbon $startDate,
        Carbon $endDate,
        Collection $attendances
    ): Collection {
        $result = collect();
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateKey = $currentDate->format('Y-m-d');
            $attendance = $attendances->get($dateKey);

            $result->push($this->formatDayData($currentDate, $attendance));
            $currentDate->addDay();
        }

        return $result;
    }

    /**
     * 日付データを整形
     *
     * @param Carbon $date
     * @param Attendance|null $attendance
     * @return array
     */
    private function formatDayData(Carbon $date, ?Attendance $attendance): array
    {
        return [
            'date' => $date->copy(),
            'attendance' => $attendance,
            'is_weekend' => $date->isWeekend(),
            'clock_in' => $attendance ? TimeFormatter::formatTime($attendance->clock_in) : null,
            'clock_out' => $attendance ? TimeFormatter::formatTime($attendance->clock_out) : null,
            'work_time' => $attendance ? TimeFormatter::minutesToTime($attendance->actual_work_time) : null,
            'overtime' => $attendance ? TimeFormatter::minutesToTime($attendance->overtime) : null,
            'night_work_time' => $attendance ? TimeFormatter::minutesToTime($attendance->night_work_time) : null,
        ];
    }

    /**
     * 対象年月のCarbonインスタンスを作成
     *
     * @param string|null $year
     * @param string|null $month
     * @return Carbon
     */
    private function createTargetDate(?string $year, ?string $month): Carbon
    {
        return Carbon::create(
            $year ?? now()->year,
            $month ?? now()->month,
            1
        );
    }

    /**
     * プルダウン用の年の選択肢を生成
     *
     * @return array
     */
    private function generateYearOptions(): array
    {
        $currentYear = now()->year;
        return collect(range($currentYear - 2, $currentYear))
            ->map(fn($year) => [
                'value' => $year,
                'label' => sprintf('%d年', $year),
                'disabled' => $year > $currentYear
            ])
            ->toArray();
    }

    /**
     * プルダウン用の月の選択肢を生成
     *
     * @param int $targetYear
     * @return array
     */
    private function generateMonthOptions(int $targetYear): array
    {
        $currentYear = now()->year;
        $currentMonth = now()->month;

        return collect(range(1, 12))
            ->map(fn($month) => [
                'value' => $month,
                'label' => sprintf('%d月', $month),
                'disabled' => $targetYear === $currentYear && $month > $currentMonth
            ])
            ->toArray();
    }
}
