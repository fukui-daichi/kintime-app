<?php

namespace App\Services;

use App\Models\Timecard;
use App\Helpers\TimeHelper;
use App\Models\User;
use App\Repositories\TimecardRepository;
use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TimecardService
{
    protected $repository;

    public function __construct(TimecardRepository $repository)
    {
        $this->repository = $repository;
    }

    // =============================================
    // 1. データ取得系
    // =============================================

    /**
     * ダッシュボード表示用データを取得
     */
    public function getDashboardData(User $user, Request $request): array
    {
        $baseData = [
            'user' => $user,
            'currentDate' => DateHelper::getCurrentJapaneseDate(),
        ];

        return match ($user->getUserType()) {
            'admin' => $this->getAdminDashboardData($baseData, $request),
            'manager' => $this->getManagerDashboardData($baseData, $request),
            default => $this->getUserDashboardData($baseData, $request)
        };
    }

    protected function getAdminDashboardData(array $baseData, Request $request): array
    {
        return array_merge($baseData, [
            'systemStats' => $this->getSystemStatistics()
        ]);
    }

    protected function getManagerDashboardData(array $baseData, Request $request): array
    {
        $timecard = $this->getTodayTimecard($baseData['user']->id);
        return array_merge($baseData, [
            'timecardButtonStatus' => $this->getStampButtonStatuses($baseData['user']->id),
            'timecard' => $timecard ? $this->timecardData($timecard) : null,
            'pendingRequests' => app(TimecardUpdateRequestService::class)
                ->getPendingRequestsForDashboard($baseData['user']->id)
        ]);
    }

    protected function getUserDashboardData(array $baseData, Request $request): array
    {
        $timecard = $this->getTodayTimecard($baseData['user']->id);
        return array_merge($baseData, [
            'timecardButtonStatus' => $this->getStampButtonStatuses($baseData['user']->id),
            'timecard' => $timecard ? $this->timecardData($timecard) : null,
            'pendingRequests' => app(TimecardUpdateRequestService::class)
                ->getPendingRequestsForDashboard($baseData['user']->id)
        ]);
    }

    /**
     * 勤怠データを表示用にフォーマット
     * @param Timecard|null $timecard 勤怠データ
     * @param Carbon|null $date 表示する日付（指定がない場合は現在日時）
     */
    public function timecardData(?Timecard $timecard = null, ?Carbon $date = null): array
    {
        if (!$timecard) {
            $date = $date ?? now();

            return [
            'date' => DateHelper::formatJapaneseDateWithoutYear($date),
            'clock_in' => '--:--',
            'clock_out' => '--:--',
            'break_time' => '--:--',
            'work_time' => '--:--',
            'overtime' => '--:--',
            'night_work' => '--:--',
            'status' => '--',
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
                TimeHelper::calculateBreakMinutes($timecard)
            ),
            'work_time' => TimeHelper::formatMinutesToTime(TimeHelper::calculateWorkMinutes($timecard)),
            'date' => ($timecard->date ?? now())->locale('ja')->isoFormat('M月D日（dd）'),
            'day_class' => ($timecard->date ?? now())->dayOfWeek === 0 ? 'bg-weekend-sun' :
                         (($timecard->date ?? now())->dayOfWeek === 6 ? 'bg-weekend-sat' : ''),
            'status' => $this->getTimecardStatusLabel($timecard)
        ];
    }

    /**
     * 勤怠ステータスラベルを取得
     */
    private function getTimecardStatusLabel(Timecard $timecard): string
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

    protected function getSystemStatistics(): array
    {
        // TODO: 管理者用システム統計データを実装
        return [];
    }

    /**
     * 今日の勤怠データを取得
     */
    public function getTodayTimecard(int $userId): ?Timecard
    {
        return $this->repository->getTodayTimecard($userId);
    }

    /**
     * 勤怠一覧表示用データを取得
     */
    public function getTimecardData(User $user, Request $request): array
    {
        $yearMonth = DateHelper::resolveYearMonth($request);
        $timecards = $this->getTimecardsByMonth($user->id, $yearMonth['year'], $yearMonth['month']);

        $timecardsArray = $timecards->toArray();

        // 申請可否判定を付与
        foreach ($timecardsArray as &$timecard) {
            $timecard['can_apply'] = isset($timecard['id']) && $timecard['id'] ? true : false;
        }

        return [
            'timecards' => $timecardsArray,
            'yearOptions' => DateHelper::getYearOptions(
                min($this->repository->getAvailableYears($user->id)),
                max($this->repository->getAvailableYears($user->id))
            ),
            'totals' => TimeHelper::calculateMonthlyTotals($timecardsArray),
            'year' => $yearMonth['year'],
            'month' => $yearMonth['month'],
            'user' => $user
        ];
    }

    /**
     * 指定ユーザーの勤怠データ（月単位、空欄も含む）
     */
    public function getTimecardsByMonth(int $userId, int $year, int $month)
    {
        $dateList = DateHelper::generateMonthDateList($year, $month);
        $timecards = $this->repository->getByUserIdAndMonth($userId, $year, $month)->keyBy(function ($tc) {
            return date('m-d', strtotime($tc->date));
        });

        $result = [];
        foreach ($dateList as $md) {
            if (isset($timecards[$md])) {
                $result[] = $this->timecardData($timecards[$md]);
            } else {
                $date = Carbon::createFromFormat('Y-m-d', $year . '-' . $md);
                $result[] = $this->timecardData(null, $date);
            }
        }
        return collect($result);
    }

    /**
     * 指定ユーザーの勤怠データ（期間指定）
     */
    public function getTimecardsByPeriod(int $userId, ?Carbon $startDate = null, ?Carbon $endDate = null)
    {
        $timecards = $this->repository->getByUserIdAndPeriod($userId, $startDate, $endDate);

        $timecards->getCollection()->transform(function ($timecard) {
            return $this->timecardData($timecard);
        });

        return $timecards;
    }

    /**
     * タイムカード編集用データをフォーマット
     */
    public function timecardEditData(Timecard $timecard): array
    {
        return [
            'id' => $timecard->id,
            'clock_in' => TimeHelper::formatDateTimeToTime($timecard->clock_in, 'H:i'),
            'clock_out' => TimeHelper::formatDateTimeToTime($timecard->clock_out, 'H:i'),
            'break_start' => TimeHelper::formatDateTimeToTime($timecard->break_start, 'H:i'),
            'break_end' => TimeHelper::formatDateTimeToTime($timecard->break_end, 'H:i'),
            'date_formatted' => DateHelper::formatJapaneseDateWithYear($timecard->date),
            'date_iso' => DateHelper::formatToIsoDate($timecard->date)
        ];
    }

    /**
     * タイムカード編集用データを取得
     */
    public function getTimecardEditData(Timecard $timecard): array
    {
        return [
            'timecard' => $this->timecardEditData($timecard),
            'user' => $timecard->user,
            'year' => $timecard->date->year,
            'month' => $timecard->date->month,
            'yearOptions' => DateHelper::getYearOptions(
                min($this->repository->getAvailableYears($timecard->user_id)),
                max($this->repository->getAvailableYears($timecard->user_id))
            )
        ];
    }

    // =============================================
    // 2. 打刻操作系
    // =============================================

    /**
     * 打刻ボタンの状態を取得
     * @param int $userId ユーザーID
     * @return array 各ボタンの状態（disabledフラグとラベル）
     */
    public function getStampButtonStatuses(int $userId): array
    {
        $timecard = $this->repository->getTodayTimecard($userId);

        return [
            'clockIn' => [
                'disabled' => $timecard !== null && $timecard->clock_in !== null,
                'label' => '出勤打刻'
            ],
            'clockOut' => [
                'disabled' => $timecard === null || $timecard->clock_out !== null,
                'label' => '退勤打刻'
            ],
            'breakStart' => [
                'disabled' => $timecard === null || $timecard->clock_out !== null || $timecard->break_start !== null || ($timecard->break_start !== null && $timecard->break_end === null),
                'label' => '休憩開始'
            ],
            'breakEnd' => [
                'disabled' => $timecard === null || $timecard->clock_out !== null || $timecard->break_start === null || $timecard->break_end !== null || $timecard->break_start === null,
                'label' => '休憩終了'
            ]
        ];
    }

    /**
     * 出勤打刻
     */
    public function clockIn(User $user): Timecard
    {
        return $this->repository->createClockInRecord($user->id);
    }

    /**
     * 退勤打刻
     */
    public function clockOut(User $user): Timecard
    {
        $timecard = $this->repository->createClockOutRecord($user->id);
        $result = TimeHelper::calculateOvertimeMinutes($timecard);
        $timecard->update([
            'overtime_minutes' => $result['overtime'],
            'night_minutes' => $result['night']
        ]);
        return $timecard;
    }

    /**
     * 休憩開始
     */
    public function startBreak(User $user): Timecard
    {
        return $this->repository->createBreakStartRecord($user->id);
    }

    /**
     * 休憩終了
     */
    public function endBreak(User $user): Timecard
    {
        return $this->repository->createBreakEndRecord($user->id);
    }

}
