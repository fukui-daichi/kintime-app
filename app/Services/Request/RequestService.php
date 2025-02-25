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
                $createData = array_merge($data, [
                    'timecard_id' => (int)$data['timecard_id']
                ]);

                $request = $this->requestRepository->create($createData);

                if ($request->request_type === RequestConstants::REQUEST_TYPE_TIMECARD) {
                    $timecard = $this->timecardRepository->findById($request->timecard_id);
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
                    $this->updateTimecard($request);
                }
            });

            return true;
        } catch (\Exception $e) {
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
}
