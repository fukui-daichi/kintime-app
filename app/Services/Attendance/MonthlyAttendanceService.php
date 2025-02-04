<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MonthlyAttendanceService
{
    private $formatter;

    public function __construct(AttendanceDataFormatter $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * 月別勤怠一覧画面用のデータを取得
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
            'years' => $this->getYearOptions(),
            'months' => $this->getMonthOptions($targetDate->year),
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
     * 月の勤怠データを取得
     *
     * @param int $userId
     * @param Carbon $targetDate
     * @return Collection
     */
    private function getMonthlyAttendance(int $userId, Carbon $targetDate): Collection
    {
        $startDate = $targetDate->copy()->startOfMonth();
        $endDate = $targetDate->copy()->endOfMonth();

        $attendances = Attendance::where('user_id', $userId)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereDate('date', '>=', $startDate)
                      ->whereDate('date', '<=', $endDate);
            })
            ->orderBy('date')
            ->get();

        // デバッグ
        // Log::debug('Attendance Query Result:', [
        //     'user_id' => $userId,
        //     'start_date' => $startDate->toDateString(),
        //     'end_date' => $endDate->toDateString(),
        //     'count' => $attendances->count(),
        //     'first_few' => $attendances->take(3)->toArray()
        // ]);

        return $this->formatter->formatMonthlyData($startDate, $endDate, $attendances);
    }

    /**
     * プルダウン用の年の選択肢を生成
     */
    private function getYearOptions(): array
    {
        $currentYear = now()->year;
        return collect(range($currentYear - 2, $currentYear))
            ->map(fn($year) => [
                'value' => $year,
                'label' => $year . '年',
                'disabled' => $year > $currentYear
            ])
            ->toArray();
    }

    /**
     * プルダウン用の月の選択肢を生成
     */
    private function getMonthOptions(int $targetYear): array
    {
        $currentYear = now()->year;
        $currentMonth = now()->month;

        return collect(range(1, 12))
            ->map(fn($month) => [
                'value' => $month,
                'label' => $month . '月',
                'disabled' => $targetYear === $currentYear && $month > $currentMonth
            ])
            ->toArray();
    }
}
