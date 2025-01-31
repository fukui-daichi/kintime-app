<?php

namespace App\Services\Attendance;

class AttendanceService
{
    private $monthlyService;
    private $clockService;

    public function __construct(
        MonthlyAttendanceService $monthlyService,
        ClockService $clockService
    ) {
        $this->monthlyService = $monthlyService;
        $this->clockService = $clockService;
    }

    /**
     * 月別勤怠一覧画面用のデータを取得
     */
    public function getMonthlyAttendanceData(int $userId, ?string $year = null, ?string $month = null): array
    {
        return $this->monthlyService->getData($userId, $year, $month);
    }

    /**
     * 打刻画面用のデータを取得
     */
    public function getDailyAttendanceData(int $userId): array
    {
        return $this->clockService->getAttendanceData($userId);
    }

    /**
     * 出勤打刻
     */
    public function clockIn(int $userId): array
    {
        return $this->clockService->clockIn($userId);
    }

    /**
     * 退勤打刻
     */
    public function clockOut(int $userId): array
    {
        return $this->clockService->clockOut($userId);
    }
}
