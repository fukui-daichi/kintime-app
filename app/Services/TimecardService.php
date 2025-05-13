<?php

namespace App\Services;

use App\Models\Timecard;
use App\Models\User;
use App\Repositories\TimecardRepository;
use App\Constants\WorkTimeConstants;
use App\Helpers\DateHelper;
use App\Helpers\TimeHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TimecardService
{
    protected $repository;

    public function __construct(TimecardRepository $repository)
    {
        $this->repository = $repository;
    }

    // --- 取得系 ---

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
            'yearOptions' => $this->getYearOptions($user->id),
            'totals' => $this->calculateMonthlyTotals($timecardsArray),
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
        $dateList = TimeHelper::getMonthDateList($year, $month);
        $timecards = $this->repository->getByUserIdAndMonth($userId, $year, $month)->keyBy(function ($tc) {
            return date('m-d', strtotime($tc->date));
        });

        $result = [];
foreach ($dateList as $md) {
    if (isset($timecards[$md])) {
        $result[] = $this->formatTimecardForDisplay($timecards[$md]);
    } else {
        $date = Carbon::createFromFormat('Y-m-d', $year . '-' . $md);
        $result[] = $this->formatEmptyTimecardForDisplay($date);
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
            return $this->formatTimecardForDisplay($timecard);
        });

        return $timecards;
    }

    /**
     * 年選択肢を取得
     */
    public function getYearOptions(int $userId): array
    {
        $years = $this->repository->getAvailableYears($userId);
        $minYear = min($years);
        $maxYear = max($years);

        // データがある最小年〜最大年+1年
        return range($minYear, $maxYear + 1);
    }

    // --- ボタン状態 ---

    /**
     * 勤怠ボタンの状態を取得
     */
    public function getTimecardButtonStatus(int $userId): array
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

    // --- 打刻系 ---

    /**
     * 出勤打刻
     */
    public function clockIn(User $user): Timecard
    {
        $this->assertCanClockIn($user->id);
        return $this->repository->createClockInRecord($user->id);
    }

    /**
     * 退勤打刻
     */
    public function clockOut(User $user): Timecard
    {
        $this->assertCanClockOut($user->id);
        $timecard = $this->repository->createClockOutRecord($user->id);
        $this->saveCalculatedTime($timecard);
        return $timecard;
    }

    /**
     * 休憩開始
     */
    public function startBreak(User $user): Timecard
    {
        $this->assertCanStartBreak($user->id);
        return $this->repository->createBreakStartRecord($user->id);
    }

    /**
     * 休憩終了
     */
    public function endBreak(User $user): Timecard
    {
        $this->assertCanEndBreak($user->id);
        return $this->repository->createBreakEndRecord($user->id);
    }

    // --- 計算・フォーマット系 ---

    /**
     * 勤怠データを表示用に整形
     */
    public function formatTimecardForDisplay(Timecard $timecard): array
    {
        return [
            'id' => $timecard->id,
            'overtime' => TimeHelper::formatMinutesToTime($timecard->overtime_minutes),
            'night_work' => TimeHelper::formatMinutesToTime($timecard->night_minutes),
            'clock_in' => $timecard->clock_in ? TimeHelper::formatDateTime($timecard->clock_in) : '--:--',
            'clock_out' => $timecard->clock_out ? TimeHelper::formatDateTime($timecard->clock_out) : '--:--',
            'break_time' => TimeHelper::formatMinutesToTime(
                $timecard->break_start && $timecard->break_end
                ? $timecard->break_start->diffInMinutes($timecard->break_end)
                : 0
            ),
            'work_time' => TimeHelper::formatMinutesToTime(
                $timecard->clock_in && $timecard->clock_out
                ? $timecard->clock_in->diffInMinutes($timecard->clock_out) -
                ($timecard->break_start && $timecard->break_end
                    ? $timecard->break_start->diffInMinutes($timecard->break_end)
                    : 0)
                : 0
            ),
            'date' => $timecard->date->locale('ja')->isoFormat('M月D日（dd）'),
            'day_class' => $timecard->date->dayOfWeek === 0 ? 'bg-weekend-sun' :
                         ($timecard->date->dayOfWeek === 6 ? 'bg-weekend-sat' : ''),
            'status' => $this->getStatusLabel($timecard)
        ];
    }

    /**
     * 空欄用の勤怠データを表示用に整形
     */
    private function formatEmptyTimecardForDisplay(Carbon $date): array
    {
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

    /**
     * ダッシュボード表示用データを取得
     */
    public function getDashboardData(User $user): array
    {
        $timecard = $this->getTodayTimecard($user->id);
        return [
            'timecardButtonStatus' => $this->getTimecardButtonStatus($user->id),
            'timecard' => $timecard ? $this->formatTimecardForDisplay($timecard) : null,
            'currentDate' => DateHelper::getJapaneseDateString(),
            'pendingRequests' => app(TimecardUpdateRequestService::class)
                ->getPendingRequestsForDashboard($user->id)
        ];
    }

    /**
     * 月間合計を計算（配列対応版）
     */
    public function calculateMonthlyTotals(array $timecards): array
    {
        $totals = [
            'days_worked' => 0,
            'total_work' => 0,
            'total_overtime' => 0,
            'total_night' => 0
        ];

        foreach ($timecards as $tc) {
            if ($tc['clock_in'] !== '--:--') {
                $totals['days_worked']++;
            }
            $totals['total_work'] += TimeHelper::timeToMinutes($tc['work_time']);
            $totals['total_overtime'] += TimeHelper::timeToMinutes($tc['overtime']);
            $totals['total_night'] += TimeHelper::timeToMinutes($tc['night_work']);
        }

        return [
            'days_worked' => $totals['days_worked'],
            'total_work' => TimeHelper::formatMinutesToTime($totals['total_work']),
            'total_overtime' => TimeHelper::formatMinutesToTime($totals['total_overtime']),
            'total_night' => TimeHelper::formatMinutesToTime($totals['total_night'])
        ];
    }

    /**
     * 残業時間と深夜時間を計算
     */
    public function calculateOvertime(Timecard $timecard): array
    {
        $totalMinutes = $timecard->clock_in->diffInMinutes($timecard->clock_out);

        $breakMinutes = 0;
        if ($timecard->break_start && $timecard->break_end) {
            $breakMinutes = $timecard->break_start->diffInMinutes($timecard->break_end);
        }

        $overtimeMinutes = $totalMinutes - $breakMinutes - (WorkTimeConstants::DEFAULT_WORK_HOURS * 60);
        $nightMinutes = $this->calculateNightTime($timecard);

        return [
            'overtime' => max($overtimeMinutes, 0),
            'night' => $nightMinutes
        ];
    }

    /**
     * 深夜時間を計算
     */
    private function calculateNightTime(Timecard $timecard): int
    {
        $nightStart = Carbon::parse($timecard->date)->setHour(WorkTimeConstants::NIGHT_START_HOUR);
        $nightEnd = Carbon::parse($timecard->date)->addDay()->setHour(WorkTimeConstants::NIGHT_END_HOUR);

        $clockIn = Carbon::parse($timecard->clock_in);
        $clockOut = Carbon::parse($timecard->clock_out);

        $nightStart = max($nightStart, $clockIn);
        $nightEnd = min($nightEnd, $clockOut);

        $nightMinutes = $nightStart < $nightEnd ? $nightStart->diffInMinutes($nightEnd) : 0;

        if ($timecard->break_start && $timecard->break_end) {
            $breakStart = Carbon::parse($timecard->break_start);
            $breakEnd = Carbon::parse($timecard->break_end);

            $breakNightStart = max($nightStart, $breakStart);
            $breakNightEnd = min($nightEnd, $breakEnd);

            if ($breakNightStart < $breakNightEnd) {
                $nightMinutes -= $breakNightStart->diffInMinutes($breakNightEnd);
            }
        }

        return max($nightMinutes, 0);
    }

    /**
     * 計算結果を保存
     */
    public function saveCalculatedTime(Timecard $timecard): void
    {
        $result = $this->calculateOvertime($timecard);
        $timecard->update([
            'overtime_minutes' => $result['overtime'],
            'night_minutes' => $result['night']
        ]);
    }

    // --- バリデーション・アサート系 ---

    /**
     * 出勤打刻可能かチェック
     */
    private function assertCanClockIn(int $userId): void
    {
        $existing = $this->repository->getTodayTimecard($userId);
        if ($existing && $existing->clock_in !== null) {
            throw new \Exception('既に出勤打刻済みです');
        }
    }

    /**
     * 退勤打刻可能かチェック
     */
    private function assertCanClockOut(int $userId): void
    {
        $timecard = $this->repository->getLatestTimecard($userId);
        if (!$timecard || $timecard->clock_out) {
            throw new \Exception('退勤打刻ができません');
        }
    }

    /**
     * 休憩開始打刻可能かチェック
     */
    private function assertCanStartBreak(int $userId): void
    {
        $timecard = $this->repository->getLatestTimecard($userId);
        if (!$timecard || $timecard->break_start) {
            throw new \Exception('休憩開始打刻ができません');
        }
    }

    /**
     * 休憩終了打刻可能かチェック
     */
    private function assertCanEndBreak(int $userId): void
    {
        $timecard = $this->repository->getLatestTimecard($userId);
        if (!$timecard || !$timecard->break_start || $timecard->break_end) {
            throw new \Exception('休憩終了打刻ができません');
        }
    }

    // --- ステータスラベル ---

    /**
     * 勤怠ステータスラベルを取得
     */
    private function getStatusLabel(Timecard $timecard): string
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

    /**
     * タイムカード編集用データを取得
     */
    public function getTimecardEditData(Timecard $timecard, Request $request): array
    {
        return [
            'timecard' => $timecard,
            'year' => $request->input('year', now()->year),
            'month' => $request->input('month', now()->month),
            'yearOptions' => $this->getYearOptions($timecard->user_id)
        ];
    }
}
