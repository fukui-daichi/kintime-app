<?php

namespace App\Services\ApprovalRequest;

use App\Models\ApprovalRequest;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApprovalRequestService
{
    /**
     * 申請種別の日本語表示用配列
     */
    private const REQUEST_TYPES = [
        'time_correction' => '時刻修正',
        'break_time_modification' => '休憩時間修正',
    ];

    /**
     * 申請状態の日本語表示用配列
     */
    private const REQUEST_STATUSES = [
        'pending' => '承認待ち',
        'approved' => '承認済み',
        'rejected' => '否認',
    ];

    /**
     * ユーザーの申請一覧を取得
     */
    public function getUserRequests(int $userId): Collection
    {
        $requests = ApprovalRequest::with(['attendance', 'approver'])
            ->where('user_id', $userId)
            ->latest()
            ->get();

        return $requests->map(function ($request) {
            return $this->formatRequestData($request);
        });
    }

    /**
     * 承認待ちの申請一覧を取得（管理者用）
     */
    public function getPendingRequests(): Collection
    {
        $requests = ApprovalRequest::with(['user', 'attendance', 'approver'])
            ->where('status', 'pending')
            ->latest()
            ->get();

        return $requests->map(function ($request) {
            return $this->formatRequestData($request);
        });
    }

    /**
     * 申請を作成
     *
     * @param array $data 申請データ
     * @return ApprovalRequest
     */
    public function createRequest(array $data): ApprovalRequest
    {
        try {
            return DB::transaction(function () use ($data) {
                // 申請を作成
                $request = ApprovalRequest::create($data);

                // 勤怠データのステータスを「承認待ち」に変更
                $request->attendance->update(['status' => 'pending_approval']);

                return $request;
            });
        } catch (\Exception $e) {
            Log::error('申請作成中にエラーが発生しました: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 申請を承認
     *
     * @param ApprovalRequest $request 申請データ
     * @param string|null $comment 承認コメント
     * @return bool
     */
    public function approveRequest(ApprovalRequest $request, ?string $comment = null): bool
    {
        try {
            return DB::transaction(function () use ($request, $comment) {
                // 申請を承認状態に更新
                $request->update([
                    'status' => 'approved',
                    'comment' => $comment,
                ]);

                // 勤怠データを修正内容で更新
                $request->attendance->update([
                    'clock_in' => $request->after_clock_in,
                    'clock_out' => $request->after_clock_out,
                    'break_time' => $request->after_break_hours * 60, // 時間を分に変換
                    'status' => 'approved',
                ]);

                return true;
            });
        } catch (\Exception $e) {
            Log::error('申請承認中にエラーが発生しました: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 申請を否認
     *
     * @param ApprovalRequest $request 申請データ
     * @param string|null $comment 否認理由
     * @return bool
     */
    public function rejectRequest(ApprovalRequest $request, ?string $comment = null): bool
    {
        try {
            return DB::transaction(function () use ($request, $comment) {
                // 申請を否認状態に更新
                $request->update([
                    'status' => 'rejected',
                    'comment' => $comment,
                ]);

                // 勤怠データのステータスを元に戻す
                $request->attendance->update(['status' => 'left']);

                return true;
            });
        } catch (\Exception $e) {
            Log::error('申請否認中にエラーが発生しました: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 申請が修正可能か確認
     *
     * @param Attendance $attendance
     * @return bool
     */
    public function canRequestModification(Attendance $attendance): bool
    {
        return !$attendance->hasPendingRequest() && $attendance->status !== 'pending_approval';
    }

    /**
     * 申請データをフォーマット
     */
    private function formatRequestData(ApprovalRequest $request): array
    {
        // 日付文字列をCarbonインスタンスに変換
        $createdAt = new Carbon($request->created_at);
        $attendanceDate = new Carbon($request->attendance->date);

        return [
            'id' => $request->id,
            'created_at' => $createdAt->format('Y/m/d H:i'),
            'attendance_date' => $attendanceDate->format('Y/m/d'),
            'request_type' => self::REQUEST_TYPES[$request->request_type] ?? $request->request_type,
            'status' => [
                'label' => self::REQUEST_STATUSES[$request->status] ?? $request->status,
                'class' => $this->getStatusClass($request->status),
            ],
            'approver_name' => $request->approver->full_name,
            'comment' => $request->comment,
            // 修正前後の時間も追加
            'before' => [
                'clock_in' => $request->before_clock_in ? Carbon::parse($request->before_clock_in)->format('H:i') : null,
                'clock_out' => $request->before_clock_out ? Carbon::parse($request->before_clock_out)->format('H:i') : null,
                'break_hours' => $request->before_break_hours,
            ],
            'after' => [
                'clock_in' => $request->after_clock_in ? Carbon::parse($request->after_clock_in)->format('H:i') : null,
                'clock_out' => $request->after_clock_out ? Carbon::parse($request->after_clock_out)->format('H:i') : null,
                'break_hours' => $request->after_break_hours,
            ],
        ];
    }

    /**
     * 申請状態に応じたCSSクラスを取得
     */
    private function getStatusClass(string $status): string
    {
        return match($status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'approved' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
