<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MonthlyAttendanceService
{
    private $formatter;

    public function __construct(AttendanceDataFormatter $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * 月別勤怠一覧画面用のデータを取得
     *
     * @param int $userId
     * @param string|null $year
     * @param string|null $month
     * @return array
     */
    public function getData(int $userId, ?string $year = null, ?string $month = null): array
    {
        $targetDate = $this->createTargetDate($year, $month);

        return [
            'attendances' => $this->getMonthlyAttendance($userId, $targetDate),
            'targetDate' => $targetDate,
            'previousMonth' => $targetDate->copy()->subMonth(),
            'nextMonth' => $targetDate->copy()->addMonth(),
            'years' => $this->getYearOptions($targetDate),
            'months' => range(1, 12),
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
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();

        return $this->formatter->formatMonthlyData($startDate, $endDate, $attendances);
    }

    /**
     * プルダウン用の年の選択肢を生成
     *
     * @param Carbon $targetDate
     * @return array
     */
    private function getYearOptions(Carbon $targetDate): array
    {
        return range(
            $targetDate->copy()->subYears(2)->year,
            $targetDate->copy()->addYears(2)->year
        );
    }
}
