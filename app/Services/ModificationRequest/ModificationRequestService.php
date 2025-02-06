<?php

namespace App\Services\ModificationRequest;

use App\Models\ModificationRequest;
use App\Models\Timecard;
use App\Models\User;
use App\Helpers\TimeFormatter;
use App\Constants\WorkTimeConstants;
use App\Constants\ModificationRequestConstants;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 申請に関するビジネスロジックを管理するサービスクラス
 */
class ModificationRequestService
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
        return ModificationRequest::with(['user', 'timecard', 'approver'])
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
        $paginator = $query->paginate(ModificationRequestConstants::PER_PAGE);

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
     * @param ModificationRequest $request
     * @return array
     */
    private function formatRequestData(ModificationRequest $request): array
    {
        return [
            'id' => $request->id,
            'created_at' => $this->formatDateTime($request->created_at),
            'user' => $this->formatUserData($request->user),
            'timecard_date' => TimeFormatter::formatDate($request->timecard->date),
            'request_type' => $this->formatRequestType($request->request_type),
            'current_time' => $this->formatTimeData($request, 'before'),
            'requested_time' => $this->formatTimeData($request, 'after'),
            'status' => $this->formatStatus($request->status),
        ];
    }

    /**
     * 日時をフォーマット
     *
     * @param Carbon $dateTime
     * @return string
     */
    private function formatDateTime(Carbon $dateTime): string
    {
        return TimeFormatter::formatDate($dateTime, 'Y/m/d H:i');
    }

    /**
     * ユーザー情報をフォーマット
     *
     * @param User $user
     * @return array
     */
    private function formatUserData(User $user): array
    {
        return [
            'name' => $user->full_name,
        ];
    }

    /**
     * 申請種別をフォーマット
     *
     * @param string $requestType
     * @return string
     */
    private function formatRequestType(string $requestType): string
    {
        return ModificationRequestConstants::REQUEST_TYPES[$requestType] ?? $requestType;
    }

    /**
     * 時間データをフォーマット
     *
     * @param ModificationRequest $request
     * @param string $prefix 'before' or 'after'
     * @return array
     */
    private function formatTimeData(ModificationRequest $request, string $prefix): array
    {
        if ($request->request_type === 'time_correction') {
            return $this->formatTimeCorrectionData($request, $prefix);
        }

        return $this->formatBreakTimeData($request, $prefix);
    }

    /**
     * 時刻修正データをフォーマット
     *
     * @param ModificationRequest $request
     * @param string $prefix
     * @return array
     */
    private function formatTimeCorrectionData(ModificationRequest $request, string $prefix): array
    {
        $clockIn = $request->{$prefix.'_clock_in'};
        $clockOut = $request->{$prefix.'_clock_out'};

        return [
            'type' => 'time',
            'data' => [
                'clock_in' => $clockIn
                    ? TimeFormatter::formatTime(Carbon::parse($clockIn))
                    : '-',
                'clock_out' => $clockOut
                    ? TimeFormatter::formatTime(Carbon::parse($clockOut))
                    : '-',
            ]
        ];
    }

    /**
     * 休憩時間データをフォーマット
     *
     * @param ModificationRequest $request
     * @param string $prefix
     * @return array
     */
    private function formatBreakTimeData(ModificationRequest $request, string $prefix): array
    {
        $breakTime = $request->{$prefix.'_break_time'};

        return [
            'type' => 'break',
            'data' => [
                'break_time' => $breakTime
                    ? TimeFormatter::minutesToTime($breakTime)
                    : '-'
            ]
        ];
    }

    /**
     * ステータスをフォーマット
     *
     * @param string $status
     * @return array
     */
    private function formatStatus(string $status): array
    {
        return [
            'label' => ModificationRequestConstants::REQUEST_STATUSES[$status] ?? $status,
            'class' => ModificationRequestConstants::STATUS_CLASSES[$status] ?? 'bg-gray-100 text-gray-800'
        ];
    }

    /************************************
     * 申請の作成・更新関連
     ************************************/

    /**
     * 新規申請を作成
     *
     * @param array $data 申請データ
     * @return ModificationRequest
     * @throws \Exception
     */
    public function createRequest(array $data): ModificationRequest
    {
        try {
            return DB::transaction(function () use ($data) {
                $request = ModificationRequest::create($data);
                $request->timecard->update(['status' => 'pending_approval']);
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
     * @param ModificationRequest $request 承認対象の申請
     * @return bool 承認処理の成功・失敗
     * @throws \Exception トランザクション処理で例外が発生した場合
     */
    public function approveRequest(ModificationRequest $request): bool
    {
        try {
            DB::transaction(function () use ($request) {
                $request->update(['status' => 'approved']);
                $updateData = $this->prepareTimecardUpdateData($request);
                $request->timecard->update($updateData);
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
     * @param ModificationRequest $request 否認対象の申請
     * @return bool 否認処理の成功・失敗
     * @throws \Exception トランザクション処理で例外が発生した場合
     */
    public function rejectRequest(ModificationRequest $request): bool
    {
        try {
            DB::transaction(function () use ($request) {
                $request->update(['status' => 'rejected']);
                $request->timecard->update(['status' => 'left']);
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
     * 勤怠データが修正申請可能か確認
     *
     * @param Timecard $timecard
     * @return bool
     */
    public function canRequestModification(Timecard $timecard): bool
    {
        return !$timecard->hasPendingRequest() &&
               $timecard->status !== 'pending_approval';
    }

    /************************************
     * 勤怠時間の計算関連
     ************************************/

    /**
     * 勤怠データの更新情報を準備
     *
     * @param ModificationRequest $request
     * @return array
     */
    private function prepareTimecardUpdateData(ModificationRequest $request): array
    {
        $timecard = $request->timecard;
        $updateData = ['status' => 'left'];

        if ($request->request_type === 'time_correction') {
            $updateData = array_merge(
                $updateData,
                $this->prepareTimeCorrection($request)
            );
        } elseif ($request->request_type === 'break_time_modification') {
            $updateData['break_time'] = $request->after_break_time;
        }

        return $this->calculateWorkTimes($updateData, $timecard);
    }

    /**
     * 時刻修正データの準備
     *
     * @param ModificationRequest $request
     * @return array
     */
    private function prepareTimeCorrection(ModificationRequest $request): array
    {
        $updateData = [];
        $date = $request->timecard->date->format('Y-m-d');

        if ($request->after_clock_in) {
            $timeOnly = Carbon::parse($request->after_clock_in)->format('H:i');
            $updateData['clock_in'] = Carbon::parse($date . ' ' . $timeOnly);
        }

        if ($request->after_clock_out) {
            $timeOnly = Carbon::parse($request->after_clock_out)->format('H:i');
            $updateData['clock_out'] = Carbon::parse($date . ' ' . $timeOnly);
        }

        return $updateData;
    }

    /**
     * 勤務時間の計算処理
     *
     * @param array $updateData 更新データ
     * @param Timecard $timecard 勤怠データ
     * @return array 計算済みの更新データ
     */
    private function calculateWorkTimes(array $updateData, Timecard $timecard): array
    {
        $date = $timecard->date->format('Y-m-d');
        $clockIn = $this->getClockInTime($updateData, $timecard, $date);
        $clockOut = $this->getClockOutTime($updateData, $timecard, $date);
        $breakTime = $updateData['break_time'] ?? $timecard->break_time ?? WorkTimeConstants::DEFAULT_BREAK_MINUTES;

        if ($clockIn && $clockOut) {
            try {
                return $this->calculateWorkTimeDetails($clockIn, $clockOut, $breakTime, $updateData);
            } catch (\Exception $e) {
                $this->logWorkTimeCalculationError($e, $clockIn, $clockOut, $breakTime);
                throw $e;
            }
        }

        return $updateData;
    }

    /**
     * 出勤時刻を取得
     *
     * @param array $updateData
     * @param Timecard $timecard
     * @param string $date
     * @return Carbon|null
     */
    private function getClockInTime(array $updateData, Timecard $timecard, string $date): ?Carbon
    {
        return isset($updateData['clock_in'])
            ? $updateData['clock_in']
            : ($timecard->clock_in
                ? Carbon::parse($date . ' ' . $timecard->clock_in->format('H:i:s'))
                : null);
    }

    /**
     * 退勤時刻を取得
     *
     * @param array $updateData
     * @param Timecard $timecard
     * @param string $date
     * @return Carbon|null
     */
    private function getClockOutTime(array $updateData, Timecard $timecard, string $date): ?Carbon
    {
        return isset($updateData['clock_out'])
            ? $updateData['clock_out']
            : ($timecard->clock_out
                ? Carbon::parse($date . ' ' . $timecard->clock_out->format('H:i:s'))
                : null);
    }

    /**
     * 勤務時間の詳細を計算
     *
     * @param Carbon $clockIn
     * @param Carbon $clockOut
     * @param int $breakTime
     * @param array $updateData
     * @return array
     */
    private function calculateWorkTimeDetails(
        Carbon $clockIn,
        Carbon $clockOut,
        int $breakTime,
        array $updateData
    ): array {
        $workMinutes = $clockIn->diffInMinutes($clockOut);
        $actualWorkMinutes = $workMinutes - $breakTime;
        $overtimeMinutes = max(0, $actualWorkMinutes - WorkTimeConstants::REGULAR_WORK_MINUTES);

        $this->logWorkTimeCalculation($clockIn, $clockOut, $workMinutes, $breakTime, $actualWorkMinutes);

        return array_merge($updateData, [
            'actual_work_time' => $actualWorkMinutes,
            'overtime' => $overtimeMinutes,
            'night_work_time' => $this->calculateNightWorkMinutes($clockIn, $clockOut),
        ]);
    }

    /**
     * 深夜時間を計算（22時〜5時の間の勤務時間）
     *
     * @param Carbon $clockIn 出勤時刻
     * @param Carbon $clockOut 退勤時刻
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
     * 勤務時間計算のログを記録
     *
     * @param Carbon $clockIn
     * @param Carbon $clockOut
     * @param int $workMinutes
     * @param int $breakTime
     * @param int $actualWorkMinutes
     * @return void
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
            'formatted_hours' => floor($actualWorkMinutes / 60),
            'formatted_minutes' => $actualWorkMinutes % 60,
            'formatted_work_time' => sprintf(
                '%d:%02d',
                floor($actualWorkMinutes / 60),
                $actualWorkMinutes % 60
            )
        ]);
    }

    /**
     * 勤務時間計算エラーのログを記録
     *
     * @param \Exception $e
     * @param Carbon $clockIn
     * @param Carbon $clockOut
     * @param int $breakTime
     * @return void
     */
    private function logWorkTimeCalculationError(
        \Exception $e,
        Carbon $clockIn,
        Carbon $clockOut,
        int $breakTime
    ): void {
        Log::error('勤務時間計算エラー', [
            'error' => $e->getMessage(),
            'clock_in' => $clockIn->format('H:i'),
            'clock_out' => $clockOut->format('H:i'),
            'break_time' => $breakTime
        ]);
    }

    /************************************
     * フォームデータの管理関連
     ************************************/

    /**
     * フォーム表示用のデータを整形して取得
     *
     * @param Timecard $timecard
     * @return array フォーム表示用のデータ
     */
    public function getFormData(Timecard $timecard): array
    {
        return [
            'currentTimecard' => $this->getCurrentTimecardData($timecard),
            'formData' => $this->getFormDefaultValues($timecard),
            'requestTypes' => $this->getRequestTypeOptions(),
            'formattedTimecard' => [
                'id' => $timecard->id,
                'clock_in' => TimeFormatter::formatTime($timecard->clock_in),
                'clock_out' => TimeFormatter::formatTime($timecard->clock_out),
                'break_time' => TimeFormatter::minutesToTime($timecard->break_time),
                'raw_timecard' => $timecard,
            ],
        ];
    }

    /**
     * 現在の勤怠情報を取得
     *
     * @param Timecard $timecard
     * @return array
     */
    private function getCurrentTimecardData(Timecard $timecard): array
    {
        return [
            'date' => TimeFormatter::formatDate($timecard->date, 'Y年m月d日'),
            'clock_in' => $timecard->clock_in
                ? TimeFormatter::formatTime($timecard->clock_in)
                : '未打刻',
            'clock_out' => $timecard->clock_out
                ? TimeFormatter::formatTime($timecard->clock_out)
                : '未打刻',
            'break_time' => TimeFormatter::minutesToTime($timecard->break_time),
            'actual_work_time' => TimeFormatter::minutesToTime($timecard->actual_work_time)
        ];
    }

    /**
     * フォームのデフォルト値を取得
     *
     * @param Timecard $timecard
     * @return array
     */
    private function getFormDefaultValues(Timecard $timecard): array
    {
        return [
            'timecard_id' => $timecard->id,
            'clock_in' => $timecard->clock_in?->format('H:i'),
            'clock_out' => $timecard->clock_out?->format('H:i'),
            'break_time' => TimeFormatter::minutesToTime($timecard->break_time)
        ];
    }

    /**
     * 申請種別の選択肢を取得
     *
     * @return array<string, string>
     */
    private function getRequestTypeOptions(): array
    {
        return [
            'time_correction' => '時刻修正',
            'break_time_modification' => '休憩時間修正'
        ];
    }
}
