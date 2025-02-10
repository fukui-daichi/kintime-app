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
use App\Repositories\Interfaces\TimecardRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * 勤怠管理の機能を担当するサービスクラス
 */
class TimecardService
{
    private $timecardRepository;

    public function __construct(TimecardRepositoryInterface $timecardRepository)
    {
        $this->timecardRepository = $timecardRepository;
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
            $workTimes = $this->calculateWorkTimes($clockIn, $clockOut, $timecard->break_time);

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
     * 勤務時間計算関連
     ************************************/

    /**
     * 勤務時間を計算
     *
     * @param Carbon $clockIn
     * @param Carbon $clockOut
     * @param int $breakTime
     * @return array
     */
    private function calculateWorkTimes(Carbon $clockIn, Carbon $clockOut, int $breakTime): array
    {
        try {
            // 退勤時刻が出勤時刻より前の場合
            if ($clockOut->lt($clockIn)) {
                throw new InvalidWorkTimeException([
                    'clock_in' => $clockIn->toTimeString(),
                    'clock_out' => $clockOut->toTimeString()
                ]);
            }

            $workMinutes = $clockIn->diffInMinutes($clockOut);
            $actualWorkMinutes = $workMinutes - $breakTime;
            $overtimeMinutes = max(0, $actualWorkMinutes - WorkTimeConstants::REGULAR_WORK_MINUTES);

            return [
                'actual_work_time' => $actualWorkMinutes,
                'overtime' => $overtimeMinutes,
                'night_work_time' => $this->calculateNightWorkMinutes($clockIn, $clockOut),
            ];
        } catch (\Exception $e) {
            $context = [
                'clock_in' => $clockIn->toTimeString(),
                'clock_out' => $clockOut->toTimeString(),
                'break_time' => $breakTime
            ];
            throw new InvalidWorkTimeException($context, $e);
        }
    }

    /**
     * 深夜時間を計算（22時〜5時の間の勤務時間）
     *
     * @param Carbon $clockIn
     * @param Carbon $clockOut
     * @return int 深夜時間（分）
     */
    private function calculateNightWorkMinutes(Carbon $clockIn, Carbon $clockOut): int
    {
        $nightWorkMinutes = 0;
        $currentTime = $clockIn->copy();
        $endTime = $clockOut->copy();

        if ($clockOut->lt($clockIn)) {
            $endTime->addDay();
        }

        while ($currentTime->lt($endTime)) {
            if ($this->isNightWorkHour((int)$currentTime->format('H'))) {
                $nightWorkMinutes++;
            }
            $currentTime->addMinute();
        }

        return $nightWorkMinutes;
    }

    /**
     * 指定時刻が深夜時間帯かどうかを判定
     *
     * @param int $hour
     * @return bool
     */
    private function isNightWorkHour(int $hour): bool
    {
        return $hour >= WorkTimeConstants::NIGHT_WORK_START_HOUR ||
               $hour < WorkTimeConstants::NIGHT_WORK_END_HOUR;
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
        $timecardsByDate = $timecards->keyBy(function ($timecard) {
            return $timecard->date->format('Y-m-d');
        });

        while ($currentDate <= $endDate) {
            $dateKey = $currentDate->format('Y-m-d');
            $timecard = $timecardsByDate->get($dateKey);

            $result->push([
                'date' => $currentDate->copy(),
                'timecard' => $timecard,
                'is_weekend' => $currentDate->isWeekend(),
                'clock_in' => $timecard ? TimeFormatter::formatTime($timecard->clock_in) : null,
                'clock_out' => $timecard ? TimeFormatter::formatTime($timecard->clock_out) : null,
                'work_time' => $timecard ? TimeFormatter::minutesToTime($timecard->actual_work_time) : null,
                'overtime' => $timecard ? TimeFormatter::minutesToTime($timecard->overtime) : null,
                'night_work_time' => $timecard ? TimeFormatter::minutesToTime($timecard->night_work_time) : null,
            ]);

            $currentDate->addDay();
        }

        return $result;
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
     * レスポンスデータを作成
     *
     * @param bool $success
     * @param string $message
     * @return array
     */
    private function createResponse(bool $success, string $message): array
    {
        return [
            'success' => $success,
            'message' => $message
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
        return !$timecard;
    }

    /**
     * 退勤打刻が可能か判定
     *
     * @param Timecard|null $timecard
     * @return bool
     */
    private function canClockOut(?Timecard $timecard): bool
    {
        return $timecard && $timecard->status === 'working';
    }

    /**
     * 勤務時間計算のログを記録
     *
     * @param Carbon $clockIn
     * @param Carbon $clockOut
     * @param int $workMinutes
     * @param int $breakTime
     * @param int $actualWorkMinutes
     */
    private function logWorkTimeCalculation(
        Carbon $clockIn,
        Carbon $clockOut,
        int $workMinutes,
        int $breakTime,
        int $actualWorkMinutes
    ): void {
        Log::debug('勤務時間計算詳細', [
            'clock_in' => $clockIn->format('H:i'),
            'clock_out' => $clockOut->format('H:i'),
            'total_minutes' => $workMinutes,
            'break_time' => $breakTime,
            'actual_minutes' => $actualWorkMinutes,
        ]);
    }
}
