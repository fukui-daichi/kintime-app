<?php

namespace App\Services\Request;

use App\Models\Request;
use App\Models\Timecard;
use App\Helpers\TimeFormatter;
use App\Constants\RequestConstants;
use App\Constants\WorkTimeConstants;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 申請に関するビジネスロジックを管理するサービスクラス
 */
class RequestService
{
    /************************************
     * 申請一覧の取得関連
     ************************************/

    /**
     * 自分の申請一覧を取得
     *
     * @param int $userId ユーザーID
     * @param string|null $status フィルター用ステータス
     * @return array{requests: array, paginator: LengthAwarePaginator}
     */
    public function getPersonalRequestList(int $userId, ?string $status = null): array
    {
        $query = $this->buildBaseRequestQuery()
            ->where('user_id', $userId);

        return $this->getRequestListWithPagination($query, $status);
    }

    /**
     * 全ての申請一覧を取得（管理者用）
     *
     * @param string|null $status フィルター用ステータス
     * @return array{requests: array, paginator: LengthAwarePaginator}
     */
    public function getAllRequestList(?string $status = null): array
    {
        $query = $this->buildBaseRequestQuery();

        return $this->getRequestListWithPagination($query, $status);
    }

    /**
     * 申請の基本クエリを構築
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildBaseRequestQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Request::with(['user', 'approver', 'timecard'])
            ->latest();
    }

    /**
     * ステータスによるフィルタリングを適用
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function applyStatusFilter(
        \Illuminate\Database\Eloquent\Builder $query,
        ?string $status
    ): \Illuminate\Database\Eloquent\Builder {
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        return $query;
    }

    /**
     * ページネーション付きの申請一覧を取得
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $status
     * @return array{requests: array, paginator: LengthAwarePaginator}
     */
    private function getRequestListWithPagination(
        \Illuminate\Database\Eloquent\Builder $query,
        ?string $status
    ): array {
        $query = $this->applyStatusFilter($query, $status);
        $paginator = $query->paginate(RequestConstants::PER_PAGE);

        $formattedRequests = [];
        foreach ($paginator as $request) {
            $formattedRequests[] = $this->formatRequestData($request);
        }

        return [
            'requests' => $formattedRequests,
            'paginator' => $paginator
        ];
    }

    /************************************
     * データフォーマット関連
     ************************************/

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
            'created_at' => TimeFormatter::formatDate($request->created_at, 'Y/m/d H:i'),
            'user' => ['name' => $request->user->full_name],
            'timecard_date' => TimeFormatter::formatDate($request->target_date),
            'request_type' => RequestConstants::REQUEST_TYPES[$request->request_type] ?? $request->request_type,
            'current_time' => $currentTime,
            'requested_time' => $requestedTime,
            'status' => [
                'label' => RequestConstants::REQUEST_STATUSES[$request->status] ?? $request->status,
                'class' => RequestConstants::STATUS_CLASSES[$request->status] ?? 'bg-gray-100 text-gray-800'
            ]
        ];
    }

    /**
     * 時間データをフォーマット
     *
     * @param Request $request
     * @param string $prefix 'before' or 'after'
     * @return array
     */
    private function formatTimeData(Request $request, string $prefix): array
    {
        if ($request->request_type === RequestConstants::REQUEST_TYPE_TIMECARD) {
            return [
                'type' => 'time',
                'data' => [
                    'clock_in' => TimeFormatter::formatTime(Carbon::parse($request->{$prefix.'_clock_in'})),
                    'clock_out' => TimeFormatter::formatTime(Carbon::parse($request->{$prefix.'_clock_out'})),
                    'break_time' => TimeFormatter::minutesToTime($request->{$prefix.'_break_time'})
                ]
            ];
        }

        return [
            'type' => 'vacation',
            'data' => [
                'vacation_type' => RequestConstants::VACATION_TYPES[$request->vacation_type] ?? '-'
            ]
        ];
    }

    /************************************
     * 申請の作成・承認関連
     ************************************/

    /**
     * 新規申請を作成
     *
     * @param array $data 申請データ
     * @return Request
     * @throws \Exception
     */
    public function createRequest(array $data): Request
    {
        try {
            return DB::transaction(function () use ($data) {
                $request = Request::create($data);

                if ($request->request_type === RequestConstants::REQUEST_TYPE_TIMECARD) {
                    $request->timecard->update(['status' => 'pending_approval']);
                }

                return $request;
            });
        } catch (\Exception $e) {
            Log::error('申請作成エラー', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 申請を承認する
     *
     * @param Request $request 承認対象の申請
     * @return bool
     * @throws \Exception
     */
    public function approveRequest(Request $request): bool
    {
        try {
            DB::transaction(function () use ($request) {
                $request->update([
                    'status' => RequestConstants::STATUS_APPROVED,
                    'approved_at' => now()
                ]);

                if ($request->request_type === RequestConstants::REQUEST_TYPE_TIMECARD) {
                    $this->updateTimecard($request);
                }
            });

            return true;
        } catch (\Exception $e) {
            Log::error('申請承認エラー', [
                'request_id' => $request->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 申請を否認する
     *
     * @param Request $request
     * @return bool
     * @throws \Exception
     */
    public function rejectRequest(Request $request): bool
    {
        try {
            DB::transaction(function () use ($request) {
                $request->update([
                    'status' => RequestConstants::STATUS_REJECTED,
                    'approved_at' => now()
                ]);

                if ($request->request_type === RequestConstants::REQUEST_TYPE_TIMECARD) {
                    $request->timecard->update(['status' => 'left']);
                }
            });

            return true;
        } catch (\Exception $e) {
            Log::error('申請否認エラー', [
                'request_id' => $request->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 勤怠修正申請の内容を適用する
     *
     * @param Request $request
     * @return bool
     */
    private function updateTimecard(Request $request): bool
    {
        $timecard = Timecard::find($request->timecard_id);

        $updateData = [
            'status' => 'left'
        ];

        // 時刻の更新
        if ($request->after_clock_in) {
            $updateData['clock_in'] = $request->after_clock_in;
        }
        if ($request->after_clock_out) {
            $updateData['clock_out'] = $request->after_clock_out;
        }
        if ($request->after_break_time) {
            $updateData['break_time'] = $request->after_break_time;
        }

        // 勤務時間の再計算
        $this->recalculateWorkTime($timecard, $updateData);

        return $timecard->update($updateData);
    }

    /**
     * 勤務時間を再計算
     *
     * @param Timecard $timecard
     * @param array $updateData 更新データ（参照渡し）
     * @return void
     */
    private function recalculateWorkTime(Timecard $timecard, array &$updateData): void
    {
        $clockIn = isset($updateData['clock_in'])
            ? Carbon::parse($updateData['clock_in'])
            : $timecard->clock_in;

        $clockOut = isset($updateData['clock_out'])
            ? Carbon::parse($updateData['clock_out'])
            : $timecard->clock_out;

        $breakTime = $updateData['break_time'] ?? $timecard->break_time;

        if ($clockIn && $clockOut) {
            $workMinutes = $clockIn->diffInMinutes($clockOut);
            $actualWorkMinutes = $workMinutes - $breakTime;
            $overtimeMinutes = max(0, $actualWorkMinutes - WorkTimeConstants::REGULAR_WORK_MINUTES);

            $updateData['actual_work_time'] = $actualWorkMinutes;
            $updateData['overtime'] = $overtimeMinutes;
            $updateData['night_work_time'] = $this->calculateNightWorkTime($clockIn, $clockOut);
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
        // 日付をまたぐ場合は翌日の日付に調整
        if ($clockOut->lt($clockIn)) {
            $clockOut->addDay();
        }

        $nightWorkMinutes = 0;
        $currentDate = $clockIn->copy()->startOfDay();
        $dates = [$currentDate];

        // 日付をまたぐ場合は翌日も追加
        if ($clockOut->day !== $clockIn->day) {
            $dates[] = $currentDate->copy()->addDay();
        }

        foreach ($dates as $date) {
            // 深夜時間帯の開始時刻（22:00）
            $nightStart = $date->copy()->setHour(WorkTimeConstants::NIGHT_WORK_START_HOUR)->setMinute(0);
            // 深夜時間帯の終了時刻（翌5:00）
            $nightEnd = $date->copy()->addDay()->setHour(WorkTimeConstants::NIGHT_WORK_END_HOUR)->setMinute(0);

            // その日の深夜時間帯と勤務時間の重なりを計算
            $periodStart = max($clockIn, $nightStart);
            $periodEnd = min($clockOut, $nightEnd);

            if ($periodEnd->gt($periodStart)) {
                $nightWorkMinutes += $periodStart->diffInMinutes($periodEnd);
            }
        }

        return $nightWorkMinutes;
    }

    /************************************
     * フォームデータ関連
     ************************************/

    /**
     * フォーム表示用のデータを取得
     *
     * @param Timecard $timecard
     * @return array
     */
    public function getFormData(Timecard $timecard): array
    {
        return [
            'timecard' => $this->formatTimecardData($timecard),
            'requestTypes' => RequestConstants::REQUEST_TYPES,
            'vacationTypes' => RequestConstants::VACATION_TYPES,
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
        $clockIn = TimeFormatter::formatTime($timecard->clock_in);
        $clockOut = TimeFormatter::formatTime($timecard->clock_out);
        $breakTime = TimeFormatter::minutesToTime($timecard->break_time);
        $actualWorkTime = TimeFormatter::minutesToTime($timecard->actual_work_time);

        return [
            'id' => $timecard->id,
            'date' => TimeFormatter::formatDate($timecard->date, 'Y年m月d日'),
            'current' => [
                'clock_in' => $clockIn ?? '未打刻',
                'clock_out' => $clockOut ?? '未打刻',
                'break_time' => $breakTime,
                'actual_work_time' => $actualWorkTime
            ],
            'form_values' => [
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'break_time' => $breakTime
            ]
        ];
    }

    /**
     * 申請が可能か確認
     *
     * @param Timecard $timecard
     * @return bool
     */
    public function canUpdateTimecard(Timecard $timecard): bool
    {
        return !$timecard->hasPendingRequest() &&
               $timecard->status !== Timecard::STATUS_PENDING_APPROVAL;
    }
}
