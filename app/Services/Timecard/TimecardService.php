<?php

namespace App\Services\Timecard;

use App\Models\Timecard;
use App\Helpers\TimeFormatter;
use App\Constants\WorkTimeConstants;
use App\Exceptions\Timecard\DuplicateClockInException;
use App\Exceptions\Timecard\ClockInNotFoundException;
use App\Exceptions\Timecard\TimecardCreateException;
use App\Exceptions\Timecard\TimecardUpdateException;
use App\Exceptions\Timecard\InvalidWorkTimeException;
use App\Helpers\TimecardHelper;
use App\Repositories\Interfaces\TimecardRepositoryInterface;
use App\Repositories\Interfaces\RequestRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * 勤怠管理の機能を担当するサービスクラス
 */
class TimecardService
{
    private $timecardRepository;
    private $requestRepository;

    public function __construct(
        TimecardRepositoryInterface $timecardRepository,
        RequestRepositoryInterface $requestRepository
    ) {
        $this->timecardRepository = $timecardRepository;
        $this->requestRepository = $requestRepository;
    }

    /************************************
     * 基本情報取得関連
     ************************************/

    /**
     * 勤怠情報に関するデータを取得
     *
     * @param int $userId ユーザーID
     * @return array{
     *   timecard: ?Timecard,
     *   canClockIn: bool,
     *   canClockOut: bool,
     *   timecardData: array
     * }
     */
    public function getDailyTimecardData(int $userId): array
    {
        $timecard = $this->timecardRepository->getTodayTimecard($userId);

        return [
            'timecard' => $timecard,
            'canClockIn' => $this->canClockIn($timecard),
            'canClockOut' => $this->canClockOut($timecard),
            'timecardData' => $this->formatTimecardData($timecard),
        ];
    }

    /************************************
     * 月次データ取得関連
     ************************************/

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
    public function getMonthlyTimecardData(int $userId, ?string $year = null, ?string $month = null): array
    {
        $targetDate = $this->createTargetDate($year, $month);
        $currentDate = now()->startOfMonth();
        $dateRange = $this->getMonthDateRange($targetDate);

        return [
            'timecards' => $this->generateCalendarData(
                $dateRange['start'],
                $dateRange['end'],
                $this->timecardRepository->getMonthlyTimecards($userId, $dateRange['start'], $dateRange['end'])
            ),
            'targetDate' => $targetDate,
            'previousMonth' => $targetDate->copy()->subMonth(),
            'nextMonth' => $targetDate->copy()->addMonth(),
            'showNextMonth' => $targetDate->copy()->addMonth()->lte($currentDate),
            'years' => $this->generateYearOptions(),
            'months' => $this->generateMonthOptions($targetDate->year),
        ];
    }

    /************************************
     * 打刻処理関連
     ************************************/

    /**
     * 出勤打刻処理
     *
     * @param int $userId ユーザーID
     * @return array{success: bool, message: string}
     */
    public function clockIn(int $userId): void
    {
        // 重複チェック
        if ($this->timecardRepository->hasTodayTimecard($userId)) {
            $context = ['user_id' => $userId, 'date' => Carbon::now()->toDateString()];
            throw new DuplicateClockInException($context);
        }

        try {
            $now = Carbon::now();
            $timecardData = [
                'user_id' => $userId,
                'date' => $now->toDateString(),
                'clock_in' => $now->toTimeString(),
                'break_time' => WorkTimeConstants::DEFAULT_BREAK_MINUTES,
                'status' => 'working',
            ];

            $this->timecardRepository->create($timecardData);
        } catch (\Exception $e) {
            $context = [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ];
            throw new TimecardCreateException($context, $e);
        }
    }

    /**
     * 退勤打刻処理
     *
     * @param int $userId ユーザーID
     * @return array{success: bool, message: string}
     */
    public function clockOut(int $userId): void
    {
        $timecard = $this->timecardRepository->getTodayWorkingTimecard($userId);

        if (!$timecard) {
            $context = ['user_id' => $userId, 'date' => Carbon::now()->toDateString()];
            throw new ClockInNotFoundException($context);
        }

        try {
            $clockIn = Carbon::parse($timecard->clock_in);
            $clockOut = Carbon::now();

            // 勤務時間の計算
            $workTimes = TimecardHelper::calculateWorkTimes($clockIn, $clockOut, $timecard->break_time);

            $updateData = array_merge(
                $workTimes,
                [
                    'clock_out' => $clockOut->toTimeString(),
                    'status' => 'left',
                ]
            );

            $this->timecardRepository->update($timecard, $updateData);
        } catch (InvalidWorkTimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            $context = [
                'user_id' => $userId,
                'timecard_id' => $timecard->id,
                'error' => $e->getMessage()
            ];
            throw new TimecardUpdateException($context, $e);
        }
    }

    /************************************
     * カレンダーデータ生成関連
     ************************************/

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

        // タイムカードデータを日付でキー化
        $timecardsByDate = $timecards->groupBy(function ($timecard) {
            return $timecard->date->format('Y-m-d');
        });

        while ($currentDate <= $endDate) {
            $dateKey = $currentDate->format('Y-m-d');
            $dayTimecards = $timecardsByDate->get($dateKey, collect());

            // その日の勤怠データがない場合は空のデータを生成
            if ($dayTimecards->isEmpty()) {
                $result->push($this->createDailyTimecardData(
                    null,
                    $currentDate->lt(Carbon::today()),
                    $currentDate->isWeekend(),
                    $currentDate->copy()
                ));
            } else {
                // 最初（もしくは最も状態が優先度の高い）勤怠データのみを使用
                $timecard = $dayTimecards->first();
                $result->push($this->createDailyTimecardData(
                    $timecard,
                    $currentDate->lt(Carbon::today()),
                    $currentDate->isWeekend(),
                    $currentDate->copy()
                ));
            }

            $currentDate->addDay();
        }

        return $result;
    }

    /**
     * 日別データを準備する
     *
     * @param Timecard|null $timecard タイムカードデータ
     * @param bool $isPastDate 過去日付かどうか
     * @param bool $isWeekend 週末かどうか
     * @param Carbon $date 日付
     * @return array 日別データ
     */
    private function createDailyTimecardData(?Timecard $timecard, bool $isPastDate, bool $isWeekend, Carbon $date): array
    {
        $statusBadge = null;
        $canRequest = false;
        $requestType = null;
        $breakTime = null;

        if ($timecard) {
            if ($timecard->isPaidVacation()) {
                // 有給休暇の場合
                $statusBadge = [
                    'text' => '有給休暇',
                    'class' => 'bg-green-100 text-green-800'
                ];
            } elseif ($timecard->isPendingRequest()) {
                // 申請中（共通）
                $statusBadge = [
                    'text' => '申請中',
                    'class' => 'bg-yellow-100 text-yellow-800'
                ];
            } elseif ($timecard->isWorking()) {
                // 勤務中
                $statusBadge = [
                    'text' => '勤務中',
                    'class' => 'bg-blue-100 text-blue-800'
                ];
            }

            // 破裳時間の表示準備
            $breakTime = TimeFormatter::minutesToTime($timecard->break_time);

            // 申請可能か判定
            $hasPendingRequest = $timecard->hasPendingRequest();
            $canRequest = $isPastDate && !$hasPendingRequest && $timecard->hasLeft();
            $requestType = 'timecard';
        } else {
            // タイムカードがない場合
            // 過去の日付で平日なら有給申請可能
            $canRequest = !$isWeekend;
            $requestType = 'vacation';
        }

        $isSunday = $date->dayOfWeek === Carbon::SUNDAY;
        $isSaturday = $date->dayOfWeek === Carbon::SATURDAY;

        return [
            'date' => $date,
            'timecard' => $timecard,
            'is_sunday' => $isSunday,
            'is_saturday' => $isSaturday,
            'is_weekend' => $isWeekend,
            'clock_in' => $timecard ? TimeFormatter::formatTime($timecard->clock_in) : null,
            'clock_out' => $timecard ? TimeFormatter::formatTime($timecard->clock_out) : null,
            'break_time' => $breakTime,
            'work_time' => $timecard ? TimeFormatter::minutesToTime($timecard->actual_work_time) : null,
            'overtime' => $timecard ? TimeFormatter::minutesToTime($timecard->overtime) : null,
            'night_work_time' => $timecard ? TimeFormatter::minutesToTime($timecard->night_work_time) : null,
            'status_badge' => $statusBadge,
            'can_request' => $canRequest,
            'request_type' => $requestType
        ];
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

    /************************************
     * ユーティリティ関連
     ************************************/

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

    /**
     * 勤怠データを表示用にフォーマット
     *
     * @param Timecard|null $timecard 勤怠データ
     * @return array フォーマット済みの勤怠データ
     */
    private function formatTimecardData(?Timecard $timecard): array
    {
        if (!$timecard) {
            return [];
        }

        return [
            'clockInTime' => $timecard->clock_in
                ? TimeFormatter::formatTime(Carbon::parse($timecard->clock_in))
                : null,
            'clockOutTime' => $timecard->clock_out
                ? TimeFormatter::formatTime(Carbon::parse($timecard->clock_out))
                : null,
            'workTime' => $timecard->actual_work_time
                ? TimeFormatter::minutesToTime($timecard->actual_work_time)
                : null,
            'overtime' => $timecard->overtime
                ? TimeFormatter::minutesToTime($timecard->overtime)
                : null,
            'nightWorkTime' => $timecard->night_work_time
                ? TimeFormatter::minutesToTime($timecard->night_work_time)
                : null,
        ];
    }

    /**
     * 出勤打刻が可能か判定
     *
     * @param Timecard|null $timecard
     * @return bool
     */
    private function canClockIn(?Timecard $timecard): bool
    {
        return !$timecard || ($timecard && !$timecard->isWorking() && !$timecard->isPaidVacation());
    }

    /**
     * 退勤打刻が可能か判定
     *
     * @param Timecard|null $timecard
     * @return bool
     */
    private function canClockOut(?Timecard $timecard): bool
    {
        return $timecard && $timecard->isWorking();
    }
}
