<?php

namespace App\Services\Request;

use App\Models\Request;
use App\Models\Timecard;
use App\Constants\RequestConstants;
use App\Constants\WorkTimeConstants;
use App\Helpers\TimeFormatter;
use App\Repositories\Interfaces\RequestRepositoryInterface;
use App\Repositories\Interfaces\TimecardRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RequestService
{
    private $requestRepository;
    private $timecardRepository;

    public function __construct(
        RequestRepositoryInterface $requestRepository,
        TimecardRepositoryInterface $timecardRepository
    ) {
        $this->requestRepository = $requestRepository;
        $this->timecardRepository = $timecardRepository;
    }

    /**
     * 自分の申請一覧を取得
     *
     * @param int $userId
     * @param string|null $status
     * @return array
     */
    public function getPersonalRequestList(int $userId, ?string $status = null): array
    {
        $paginator = $this->requestRepository->getPaginatedUserRequests($userId, $status);

        return [
            'requests' => $paginator->items()
                ? collect($paginator->items())->map(fn($request) => $this->formatRequestData($request))->all()
                : [],
            'paginator' => $paginator
        ];
    }

    /**
     * 全ての申請一覧を取得（管理者用）
     *
     * @param string|null $status
     * @return array
     */
    public function getAllRequestList(?string $status = null): array
    {
        $paginator = $this->requestRepository->getPaginatedAllRequests($status);

        return [
            'requests' => $paginator->items()
                ? collect($paginator->items())->map(fn($request) => $this->formatRequestData($request))->all()
                : [],
            'paginator' => $paginator
        ];
    }

    /**
     * 申請を作成
     *
     * @param array $data
     * @return Request
     * @throws \Exception
     */
    public function createRequest(array $data): Request
    {
        try {
            return DB::transaction(function () use ($data) {
                // データ内にtimecard_idが存在するかチェック
                $createData = $data;

                // 有給休暇申請の場合はtimecard_idが不要
                if (isset($data['request_type']) &&
                    $data['request_type'] === RequestConstants::REQUEST_TYPE_PAID_VACATION) {
                    // timecard_idが設定されていれば削除
                    if (isset($createData['timecard_id'])) {
                        unset($createData['timecard_id']);
                    }
                } else {
                    // 勤怠修正申請の場合はtimecard_idが必要
                    if (isset($createData['timecard_id'])) {
                        $createData['timecard_id'] = (int)$data['timecard_id'];
                    }
                }

                $request = $this->requestRepository->create($createData);

                // 勤怠修正申請の場合はタイムカードのステータスを更新
                if (isset($data['request_type']) &&
                    $data['request_type'] === RequestConstants::REQUEST_TYPE_TIMECARD &&
                    isset($data['timecard_id'])) {
                    $timecard = $this->timecardRepository->findById((int)$data['timecard_id']);
                    if ($timecard) {
                        $timecard->update(['status' => 'pending_approval']);
                    }
                }

                return $request;
            });
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 申請を承認
     *
     * @param Request $request
     * @return bool
     * @throws \Exception
     */
    public function approveRequest(Request $request): bool
    {
        try {
            DB::transaction(function () use ($request) {
                $this->requestRepository->update($request, [
                    'status' => RequestConstants::STATUS_APPROVED,
                    'approved_at' => now()
                ]);

                if ($request->request_type === RequestConstants::REQUEST_TYPE_TIMECARD) {
                    // 勤怠修正申請の場合
                    $this->updateTimecard($request);
                } elseif ($request->request_type === RequestConstants::REQUEST_TYPE_PAID_VACATION) {
                    // 有給休暇申請の場合
                    $this->registerVacationTimecard($request);
                }
            });

            return true;
        } catch (\Exception $e) {
            Log::error('申請承認処理エラー', [
                'request_id' => $request->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * 申請を否認
     *
     * @param Request $request
     * @return bool
     * @throws \Exception
     */
    public function rejectRequest(Request $request): bool
    {
        try {
            DB::transaction(function () use ($request) {
                $this->requestRepository->update($request, [
                    'status' => RequestConstants::STATUS_REJECTED,
                    'approved_at' => now()
                ]);

                if ($request->request_type === RequestConstants::REQUEST_TYPE_TIMECARD) {
                    $timecard = $this->timecardRepository->findById($request->timecard_id);
                    $timecard->update(['status' => 'left']);
                }
            });

            return true;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 申請フォーム用のデータを取得
     *
     * @param Timecard $timecard
     * @return array
     */
    public function getFormData(Timecard $timecard): array
    {
        $formattedTimecard = $this->formatTimecardDataForDisplay($timecard);

        $formData = [
            'timecard_id' => $timecard->id,
            'clock_in' => TimeFormatter::toHourMinute($timecard->clock_in),
            'clock_out' => TimeFormatter::toHourMinute($timecard->clock_out),
            'break_time' => TimeFormatter::minutesToTime($timecard->break_time),
        ];

        return [
            'currentTimecard' => $this->formatTimecardData($timecard),
            'formattedTimecard' => $formattedTimecard,
            'formData' => $formData,
            'requestTypes' => RequestConstants::REQUEST_TYPES,
            'vacationTypes' => RequestConstants::VACATION_TYPES,
        ];
    }

    /**
     * 申請データを表示用にフォーマット
     *
     * @param Request $request
     * @return array
     */
    private function formatRequestData(Request $request): array
    {
        $currentTime = $this->formatTimeData($request, 'before');
        $requestedTime = $this->formatTimeData($request, 'after');

        return [
            'id' => $request->id,
            'created_at' => Carbon::parse($request->created_at)->format('Y/m/d H:i'),
            'user' => [
                'name' => $request->user->full_name
            ],
            'timecard_date' => Carbon::parse($request->target_date)->format('Y/m/d'),
            'request_type' => RequestConstants::REQUEST_TYPES[$request->request_type] ?? $request->request_type,
            'current_time' => $currentTime,
            'requested_time' => $requestedTime,
            'status' => [
                'label' => RequestConstants::REQUEST_STATUSES[$request->status] ?? $request->status,
                'class' => RequestConstants::STATUS_CLASSES[$request->status] ?? ''
            ]
        ];
    }

    /**
     * 時間データをフォーマット
     *
     * @param Request $request
     * @param string $prefix
     * @return array
     */
    private function formatTimeData(Request $request, string $prefix): array
    {
        if ($request->request_type === RequestConstants::REQUEST_TYPE_TIMECARD) {
            return [
                'type' => 'time',
                'data' => [
                    'clock_in' => $request->{$prefix.'_clock_in'}
                        ? Carbon::parse($request->{$prefix.'_clock_in'})->format('H:i')
                        : null,
                    'clock_out' => $request->{$prefix.'_clock_out'}
                        ? Carbon::parse($request->{$prefix.'_clock_out'})->format('H:i')
                        : null,
                    'break_time' => TimeFormatter::minutesToTime($request->{$prefix.'_break_time'})
                ]
            ];
        }

        return [
            'type' => 'vacation',
            'data' => [
                'vacation_type' => RequestConstants::VACATION_TYPES[$request->vacation_type] ?? null
            ]
        ];
    }

    /**
     * 勤怠データをフォーマット
     *
     * @param Timecard $timecard
     * @return array
     */
    private function formatTimecardData(Timecard $timecard): array
    {
        return [
            'date' => $timecard->date->format('Y年m月d日'),
            'clock_in' => $timecard->clock_in
                ? Carbon::parse($timecard->clock_in)->format('H:i')
                : '未打刻',
            'clock_out' => $timecard->clock_out
                ? Carbon::parse($timecard->clock_out)->format('H:i')
                : '未打刻',
            'break_time' => TimeFormatter::minutesToTime($timecard->break_time),
            'actual_work_time' => TimeFormatter::minutesToTime($timecard->actual_work_time)
        ];
    }

    /**
     * 申請フォーム表示用に勤怠データをフォーマット
     *
     * @param Timecard $timecard
     * @return array
     */
    private function formatTimecardDataForDisplay(Timecard $timecard): array
    {
        return [
            'clock_in' => $timecard->clock_in
                ? Carbon::parse($timecard->clock_in)->format('H:i')
                : '-',
            'clock_out' => $timecard->clock_out
                ? Carbon::parse($timecard->clock_out)->format('H:i')
                : '-',
            'break_time' => $timecard->break_time
                ? sprintf('%d時間', $timecard->break_time / 60)
                : '-'
        ];
    }

    /**
     * 勤怠修正申請の内容を適用
     *
     * @param Request $request
     * @return bool
     */
    private function updateTimecard(Request $request): bool
    {
        try {
            $timecard = $this->timecardRepository->findById($request->timecard_id);
            if (!$timecard) {
                throw new \Exception('勤怠データが見つかりません');
            }

            $updateData = [
                'status' => 'left'
            ];

            // 時刻の更新処理を修正
            if ($request->after_clock_in) {
                $updateData['clock_in'] = Carbon::parse($timecard->date->format('Y-m-d') . ' ' .
                    Carbon::parse($request->after_clock_in)->format('H:i:s'));
            }

            if ($request->after_clock_out) {
                $updateData['clock_out'] = Carbon::parse($timecard->date->format('Y-m-d') . ' ' .
                    Carbon::parse($request->after_clock_out)->format('H:i:s'));
            }

            if ($request->after_break_time) {
                $updateData['break_time'] = $request->after_break_time;
            }

            // 勤務時間の再計算
            if (isset($updateData['clock_in']) || isset($updateData['clock_out']) || isset($updateData['break_time'])) {
                $clockIn = isset($updateData['clock_in'])
                    ? Carbon::parse($updateData['clock_in'])
                    : Carbon::parse($timecard->clock_in);

                $clockOut = isset($updateData['clock_out'])
                    ? Carbon::parse($updateData['clock_out'])
                    : Carbon::parse($timecard->clock_out);

                $breakTime = $updateData['break_time'] ?? $timecard->break_time;

                $this->recalculateWorkTime($clockIn, $clockOut, $breakTime, $updateData);
            }

            return $this->timecardRepository->update($timecard, $updateData);

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 勤務時間を再計算
     *
     * @param Carbon $clockIn
     * @param Carbon $clockOut
     * @param int $breakTime
     * @param array &$updateData
     * @return void
     */
    private function recalculateWorkTime(
        Carbon $clockIn,
        Carbon $clockOut,
        int $breakTime,
        array &$updateData
    ): void {
        try {
            $workMinutes = $clockIn->copy()->diffInMinutes($clockOut);
            $actualWorkMinutes = $workMinutes - $breakTime;

            $updateData['actual_work_time'] = $actualWorkMinutes;
            $updateData['overtime'] = max(0, $actualWorkMinutes - WorkTimeConstants::REGULAR_WORK_MINUTES);
            $updateData['night_work_time'] = $this->calculateNightWorkTime($clockIn, $clockOut);

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 深夜時間を計算
     *
     * @param Carbon $clockIn
     * @param Carbon $clockOut
     * @return int
     */
    private function calculateNightWorkTime(Carbon $clockIn, Carbon $clockOut): int
    {
        $nightWorkMinutes = 0;
        $currentTime = $clockIn->copy();
        $endTime = $clockOut->copy();

        // 日付をまたぐ場合は翌日の日付に調整
        if ($clockOut->lt($clockIn)) {
            $endTime->addDay();
        }

        while ($currentTime->lt($endTime)) {
            $hour = (int)$currentTime->format('H');
            if ($hour >= WorkTimeConstants::NIGHT_WORK_START_HOUR ||
                $hour < WorkTimeConstants::NIGHT_WORK_END_HOUR) {
                $nightWorkMinutes++;
            }
            $currentTime->addMinute();
        }

        return $nightWorkMinutes;
    }

    /**
     * 申請が可能か確認
     *
     * @param Timecard $timecard
     * @return bool
     */
    public function canUpdateTimecard(Timecard $timecard): bool
    {
        return !$this->requestRepository->getPendingRequestByTimecardId($timecard->id);
    }

    /**
     * 指定された日付が過去日付かどうかを判定する
     *
     * @param Carbon|string $date 対象日付
     * @return bool 過去日付の場合はtrue
     */
    public function isPastDate($date): bool
    {
        $targetDate = $date instanceof Carbon ? $date : Carbon::parse($date);
        return $targetDate->lt(Carbon::today());
    }

    /**
     * 指定された日付が未来日付かどうかを判定する
     *
     * @param Carbon|string $date 対象日付
     * @return bool 未来日付の場合はtrue
     */
    public function isFutureDate($date): bool
    {
        $targetDate = $date instanceof Carbon ? $date : Carbon::parse($date);
        return $targetDate->gte(Carbon::today());
    }

    /**
     * 指定された日付が土日（週末）かどうかをチェックする
     *
     * @param Carbon|string $date 対象日付
     * @return bool 土日の場合はtrue
     */
    public function isWeekend($date): bool
    {
        $targetDate = $date instanceof Carbon ? $date : Carbon::parse($date);
        return $targetDate->isWeekend();
    }

    /**
     * 勤怠修正申請フォーム用のデータを取得
     *
     * @param Timecard $timecard タイムカードデータ
     * @return array フォーム表示用データ
     */
    public function getTimecardModificationFormData(Timecard $timecard): array
    {
        $formattedTimecard = $this->formatTimecardDataForDisplay($timecard);

        $formData = [
            'timecard_id' => $timecard->id,
            'target_date' => $timecard->date->format('Y-m-d'),
            'clock_in' => TimeFormatter::toHourMinute($timecard->clock_in),
            'clock_out' => TimeFormatter::toHourMinute($timecard->clock_out),
            'break_time' => TimeFormatter::minutesToTime($timecard->break_time),
        ];

        return [
            'currentTimecard' => $this->formatTimecardData($timecard),
            'formattedTimecard' => $formattedTimecard,
            'formData' => $formData,
            'requestTypes' => RequestConstants::REQUEST_TYPES,
            'vacationTypes' => RequestConstants::VACATION_TYPES,
            'defaultRequestType' => RequestConstants::REQUEST_TYPE_TIMECARD,
            'isPastDate' => true,
            'isFutureDate' => false,
            'targetDate' => $timecard->date->format('Y-m-d'),
            'displayDate' => $timecard->date->format('Y年m月d日'),
        ];
    }

    /**
     * 有給休暇申請フォーム用のデータを取得
     *
     * @param string|null $targetDate 対象日付（指定がない場合は本日）
     * @return array フォーム表示用データ
     */
    public function getVacationRequestFormData(?string $targetDate = null): array
    {
        $date = $targetDate ? Carbon::parse($targetDate) : Carbon::today();

        return [
            'currentTimecard' => null,
            'formattedTimecard' => null,
            'formData' => [
                'timecard_id' => null,
                'target_date' => $date->format('Y-m-d'),
            ],
            'requestTypes' => RequestConstants::REQUEST_TYPES,
            'vacationTypes' => RequestConstants::VACATION_TYPES,
            'defaultRequestType' => RequestConstants::REQUEST_TYPE_PAID_VACATION,
            'isPastDate' => false,
            'isFutureDate' => true,
            'targetDate' => $date->format('Y-m-d'),
            'displayDate' => $date->format('Y年m月d日'),
        ];
    }

        /**
     * 有給休暇申請に基づいてタイムカードを作成または更新
     *
     * @param Request $request
     * @return bool
     */
    private function registerVacationTimecard(Request $request): bool
    {
        try {
            // 対象日のタイムカードを検索
            $timecard = $this->timecardRepository->getTimecardByDate(
                $request->user_id,
                Carbon::parse($request->target_date)
            );

            $timecardData = [
                'user_id' => $request->user_id,
                'date' => $request->target_date,
                'status' => Timecard::STATUS_PAID_VACATION,
                'vacation_type' => $request->vacation_type,
                // 有給休暇の場合は勤務時間関連のフィールドをクリア
                'clock_in' => null,
                'clock_out' => null,
                'break_time' => null,
                'actual_work_time' => $this->calculateVacationWorkTime($request->vacation_type),
                'overtime' => 0,
                'night_work_time' => 0
            ];

            if ($timecard) {
                // 既存のタイムカードを更新
                return $this->timecardRepository->update($timecard, $timecardData);
            } else {
                // 新規タイムカードを作成
                $this->timecardRepository->create($timecardData);
                return true;
            }
        } catch (\Exception $e) {
            Log::error('有給休暇タイムカード作成エラー', [
                'request_id' => $request->id,
                'target_date' => $request->target_date,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 有給休暇種別に基づいて勤務時間を計算
     *
     * @param string $vacationType 有給休暇種別（full/am/pm）
     * @return int 勤務時間（分）
     */
    private function calculateVacationWorkTime(string $vacationType): int
    {
        // 有給休暇種別に応じた勤務時間を計算
        // 全日: 0分、半休: 通常勤務時間の半分
        $regularWorkMinutes = WorkTimeConstants::REGULAR_WORK_MINUTES;

        switch ($vacationType) {
            case Timecard::VACATION_TYPE_FULL:
                return 0; // 全日休暇は勤務時間0
            case Timecard::VACATION_TYPE_AM:
            case Timecard::VACATION_TYPE_PM:
                return $regularWorkMinutes / 2; // 半休は半日分の勤務時間
            default:
                return 0;
        }
    }
}
