<?php

namespace App\Services\Timecard;

use App\Models\Timecard;
use App\Helpers\TimeFormatter;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * 月次勤怠データの取得と加工を担当するサービスクラス
 */
class MonthlyTimecardService
{
    /**
     * 月別勤怠一覧画面用のデータを取得
     *
     * @param int $userId ユーザーID
     * @param string|null $year 年（nullの場合は現在年）
     * @param string|null $month 月（nullの場合は現在月）
     * @return array{
     *   timecards: Collection,
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
            'timecards' => $this->getMonthlyTimecard($userId, $targetDate),
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
    private function getMonthlyTimecard(int $userId, Carbon $targetDate): Collection
    {
        // 月の範囲を設定
        $dateRange = $this->getMonthDateRange($targetDate);

        // DBから勤怠データを取得
        $timecards = $this->fetchTimecardData($userId, $dateRange);

        // カレンダーデータを生成
        return $this->generateCalendarData($dateRange['start'], $dateRange['end'], $timecards);
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
    private function fetchTimecardData(int $userId, array $dateRange): Collection
    {
        return Timecard::where('user_id', $userId)
            ->whereBetween('date', [
                $dateRange['start']->toDateString(),
                $dateRange['end']->toDateString()
            ])
            ->orderBy('date')
            ->get()
            ->keyBy(fn($timecard) => $timecard->date->format('Y-m-d'));
    }

    /**
     * カレンダーデータを生成
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param Collection $timecards
     * @return Collection
     */
    private function generateCalendarData(
        Carbon $startDate,
        Carbon $endDate,
        Collection $timecards
    ): Collection {
        $result = collect();
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateKey = $currentDate->format('Y-m-d');
            $timecard = $timecards->get($dateKey);

            $result->push($this->formatDayData($currentDate, $timecard));
            $currentDate->addDay();
        }

        return $result;
    }

    /**
     * 日付データを整形
     *
     * @param Carbon $date
     * @param Timecard|null $timecard
     * @return array
     */
    private function formatDayData(Carbon $date, ?Timecard $timecard): array
    {
        return [
            'date' => $date->copy(),
            'timecard' => $timecard,
            'is_weekend' => $date->isWeekend(),
            'clock_in' => $timecard ? TimeFormatter::formatTime($timecard->clock_in) : null,
            'clock_out' => $timecard ? TimeFormatter::formatTime($timecard->clock_out) : null,
            'work_time' => $timecard ? TimeFormatter::minutesToTime($timecard->actual_work_time) : null,
            'overtime' => $timecard ? TimeFormatter::minutesToTime($timecard->overtime) : null,
            'night_work_time' => $timecard ? TimeFormatter::minutesToTime($timecard->night_work_time) : null,
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
