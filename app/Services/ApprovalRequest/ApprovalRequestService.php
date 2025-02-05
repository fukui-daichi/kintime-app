<?php

namespace App\Services\ApprovalRequest;

use App\Models\ApprovalRequest;
use App\Models\Attendance;
use App\Helpers\TimeFormatter;
use App\Constants\WorkTimeConstants;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 申請に関するビジネスロジックを管理するサービスクラス
 */
class ApprovalRequestService
{
    /**
     * @var array<string, string> 申請種別の日本語表示
     */
    private const REQUEST_TYPES = [
        'time_correction' => '時刻修正',
        'break_time_modification' => '休憩時間修正',
    ];

    /**
     * @var array<string, string> 申請状態の日本語表示
     */
    private const REQUEST_STATUSES = [
        'pending' => '承認待ち',
        'approved' => '承認済み',
        'rejected' => '否認',
    ];

    /**
     * @var array<string, string> 申請状態に対応するCSSクラス
     */
    private const STATUS_CLASSES = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'approved' => 'bg-green-100 text-green-800',
        'rejected' => 'bg-red-100 text-red-800',
    ];

    /**
     * 指定したユーザーの申請一覧を取得
     *
     * @param int $userId ユーザーID
     * @return Collection フォーマット済みの申請一覧
     */
    public function getUserRequests(int $userId): Collection
    {
        return ApprovalRequest::with(['attendance', 'approver'])
            ->where('user_id', $userId)
            ->latest()
            ->get()
            ->map(fn($request) => $this->formatRequestData($request));
    }

    /**
     * ステータスでフィルタリングした申請一覧を取得
     *
     * @param string|null $status フィルタリングするステータス
     * @param int $perPage 1ページの表示件数
     * @return LengthAwarePaginator
     */
    public function getFilteredRequests(?string $status = 'pending', int $perPage = 20): LengthAwarePaginator
    {
        return ApprovalRequest::with(['user', 'attendance', 'approver'])
            ->when($status && $status !== 'all', fn($query) => $query->where('status', $status))
            ->latest()
            ->paginate($perPage);
    }

    /**
     * 承認待ちの申請一覧を取得（管理者用）
     *
     * @return Collection
     */
    public function getPendingRequests(): Collection
    {
        $requests = $this->getFilteredRequests('pending')->items();
        return collect($requests)->map(fn($request) => $this->formatRequestData($request));
    }

    /**
     * 新規申請を作成
     *
     * @param array $data 申請データ
     * @return ApprovalRequest
     * @throws \Exception
     */
    public function createRequest(array $data): ApprovalRequest
    {
        try {
            // トランザクション開始
            return DB::transaction(function () use ($data) {
                // 申請を作成
                $request = ApprovalRequest::create($data);

                // 関連する勤怠データのステータスを更新
                $request->attendance->update(['status' => 'pending_approval']);

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
     * @param ApprovalRequest $request 承認対象の申請
     * @return bool 承認処理の成功・失敗
     * @throws \Exception トランザクション処理で例外が発生した場合
     */
    public function approveRequest(ApprovalRequest $request): bool
    {
        try {
            // トランザクションで処理を実行
            DB::transaction(function () use ($request) {
                // 申請を承認状態に更新
                $request->update(['status' => 'approved']);

                // 勤怠データの更新
                $updateData = $this->prepareAttendanceUpdateData($request);
                $request->attendance->update($updateData);
            });

            return true;

        } catch (\Exception $e) {
            // エラーログを記録
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
     * @param ApprovalRequest $request 否認対象の申請
     * @return bool 否認処理の成功・失敗
     * @throws \Exception トランザクション処理で例外が発生した場合
     */
    public function rejectRequest(ApprovalRequest $request): bool
    {
        try {
            // トランザクションで処理を実行
            DB::transaction(function () use ($request) {
                // 申請を否認状態に更新
                $request->update([
                    'status' => 'rejected'
                ]);

                // 勤怠データのステータスを元に戻す
                $request->attendance->update([
                    'status' => 'left'
                ]);
            });

            return true;

        } catch (\Exception $e) {
            // エラーログを記録
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
     * @param Attendance $attendance
     * @return bool
     */
    public function canRequestModification(Attendance $attendance): bool
    {
        return !$attendance->hasPendingRequest() &&
               $attendance->status !== 'pending_approval';
    }

    /**
     * 申請フォーム表示用のデータを取得
     *
     * @param Attendance $attendance
     * @return array
     */
    public function getRequestFormData(Attendance $attendance): array
    {
        return [
            'id' => $attendance->id,
            'date' => TimeFormatter::formatDate($attendance->date, 'Y年m月d日'),
            'clock_in' => $attendance->clock_in
                ? TimeFormatter::formatTime($attendance->clock_in)
                : '未打刻',
            'clock_out' => $attendance->clock_out
                ? TimeFormatter::formatTime($attendance->clock_out)
                : '未打刻',
            'break_time' => $attendance->break_time
                ? TimeFormatter::minutesToTime($attendance->break_time)
                : '未設定',
            'actual_work_time' => $attendance->actual_work_time
                ? TimeFormatter::minutesToTime($attendance->actual_work_time)
                : '未計算',
            'raw_attendance' => $attendance,
        ];
    }

    /**
     * 申請データを表示用にフォーマット
     *
     * @param ApprovalRequest $request
     * @return array
     */
    private function formatRequestData(ApprovalRequest $request): array
    {
        return [
            'id' => $request->id,
            'created_at' => TimeFormatter::formatDate($request->created_at, 'Y/m/d H:i'),
            'attendance_date' => TimeFormatter::formatDate($request->attendance->date),
            'request_type' => self::REQUEST_TYPES[$request->request_type] ?? $request->request_type,
            'status' => [
                'label' => self::REQUEST_STATUSES[$request->status] ?? $request->status,
                'class' => self::STATUS_CLASSES[$request->status] ?? 'bg-gray-100 text-gray-800'
            ],
            'approver_name' => $request->approver->full_name,
            'comment' => $request->comment,
            'time_data' => [
                'before' => $this->formatTimeData($request, 'before'),
                'after' => $this->formatTimeData($request, 'after')
            ]
        ];
    }

    /**
     * 申請の時間データをフォーマット
     *
     * @param ApprovalRequest $request
     * @param string $type 'before' or 'after'
     * @return array
     */
    private function formatTimeData(ApprovalRequest $request, string $type): array
    {
        $prefix = "{$type}_";
        return [
            'clock_in' => $request->{$prefix.'clock_in'}
                ? TimeFormatter::formatTime(Carbon::parse($request->{$prefix.'clock_in'}))
                : null,
            'clock_out' => $request->{$prefix.'clock_out'}
                ? TimeFormatter::formatTime(Carbon::parse($request->{$prefix.'clock_out'}))
                : null,
            'break_time' => $request->{$prefix.'break_time'}
                ? TimeFormatter::minutesToTime($request->{$prefix.'break_time'})
                : null
        ];
    }

    /**
     * 勤怠データの更新情報を準備
     *
     * @param ApprovalRequest $request
     * @return array
     */
    private function prepareAttendanceUpdateData(ApprovalRequest $request): array
    {
        $attendance = $request->attendance;
        $updateData = ['status' => 'left'];

        if ($request->request_type === 'time_correction') {
            $updateData = array_merge(
                $updateData,
                $this->prepareTimeCorrection($request)
            );
        } elseif ($request->request_type === 'break_time_modification') {
            $updateData['break_time'] = $request->after_break_time;
        }

        return $this->calculateWorkTimes($updateData, $attendance);
    }

    /**
     * 時刻修正データの準備
     *
     * @param ApprovalRequest $request
     * @return array
     */
    private function prepareTimeCorrection(ApprovalRequest $request): array
    {
        $updateData = [];
        $date = $request->attendance->date->format('Y-m-d');

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
     * @param Attendance $attendance 勤怠データ
     * @return array 計算済みの更新データ
     */
    private function calculateWorkTimes(array $updateData, Attendance $attendance): array
    {
        // 出勤・退勤時刻を取得
        $date = $attendance->date->format('Y-m-d');

        // 出勤時刻の処理
        $clockIn = isset($updateData['clock_in'])
            ? $updateData['clock_in']
            : ($attendance->clock_in
                ? Carbon::parse($date . ' ' . $attendance->clock_in->format('H:i:s'))
                : null);

        // 退勤時刻の処理
        $clockOut = isset($updateData['clock_out'])
            ? $updateData['clock_out']
            : ($attendance->clock_out
                ? Carbon::parse($date . ' ' . $attendance->clock_out->format('H:i:s'))
                : null);

        // 休憩時間（分単位）
        $breakTime = $updateData['break_time'] ?? $attendance->break_time ?? WorkTimeConstants::DEFAULT_BREAK_MINUTES;

        if ($clockIn && $clockOut) {
            try {
                // 総勤務時間を計算
                $workMinutes = $clockIn->diffInMinutes($clockOut);

                // 実労働時間 = 総勤務時間 - 休憩時間
                $actualWorkMinutes = $workMinutes - $breakTime;

                // デバッグログ
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

                // 残業時間 = 実労働時間 - 所定労働時間
                $overtimeMinutes = max(0, $actualWorkMinutes - WorkTimeConstants::REGULAR_WORK_MINUTES);

                $updateData['actual_work_time'] = $actualWorkMinutes;
                $updateData['overtime'] = $overtimeMinutes;
                $updateData['night_work_time'] = $this->calculateNightWorkMinutes($clockIn, $clockOut);

            } catch (\Exception $e) {
                Log::error('勤務時間計算エラー', [
                    'error' => $e->getMessage(),
                    'clock_in' => $clockIn->format('H:i'),
                    'clock_out' => $clockOut->format('H:i'),
                    'break_time' => $breakTime
                ]);
                throw $e;
            }
        }

        return $updateData;
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

        // 日付をまたぐ場合の処理
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

    /**
     * フォームのデフォルト値を取得
     *
     * @param Attendance $attendance
     * @return array
     */
    private function getFormDefaultValues(Attendance $attendance): array
    {
        return [
            'attendance_id' => $attendance->id,
            'clock_in' => $attendance->clock_in?->format('H:i'),
            'clock_out' => $attendance->clock_out?->format('H:i'),
            'break_time' => TimeFormatter::minutesToTime($attendance->break_time)
        ];
    }

    /**
     * 現在の勤怠情報を取得
     *
     * @param Attendance $attendance
     * @return array
     */
    private function getCurrentAttendanceData(Attendance $attendance): array
    {
        return [
            'date' => TimeFormatter::formatDate($attendance->date, 'Y年m月d日'),
            'clock_in' => $attendance->clock_in
                ? TimeFormatter::formatTime($attendance->clock_in)
                : '未打刻',
            'clock_out' => $attendance->clock_out
                ? TimeFormatter::formatTime($attendance->clock_out)
                : '未打刻',
            'break_time' => TimeFormatter::minutesToTime($attendance->break_time),
            'actual_work_time' => TimeFormatter::minutesToTime($attendance->actual_work_time)
        ];
    }

    /**
     * フォーム表示用のデータを整形して取得
     *
     * @param Attendance $attendance
     * @return array フォーム表示用のデータ
     */
    public function getFormData(Attendance $attendance): array
    {
        return [
            'currentAttendance' => $this->getCurrentAttendanceData($attendance),
            'formData' => $this->getFormDefaultValues($attendance),
            'requestTypes' => $this->getRequestTypeOptions(),
            'formattedAttendance' => [
                'id' => $attendance->id,
                'clock_in' => TimeFormatter::formatTime($attendance->clock_in),
                'clock_out' => TimeFormatter::formatTime($attendance->clock_out),
                'break_time' => TimeFormatter::minutesToTime($attendance->break_time),
                'raw_attendance' => $attendance,
            ],
        ];
    }
}
