<?php

namespace App\Services\ApprovalRequest;

use App\Models\ApprovalRequest;
use App\Models\Attendance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApprovalRequestService
{
    /**
     * ユーザーの申請一覧を取得
     *
     * @param int $userId ユーザーID
     * @return Collection 申請一覧
     */
    public function getUserRequests(int $userId): Collection
    {
        return ApprovalRequest::with(['attendance', 'approver'])
            ->where('user_id', $userId)
            ->latest()
            ->get();
    }

    /**
     * 承認待ちの申請一覧を取得（管理者用）
     *
     * @return Collection 承認待ち申請一覧
     */
    public function getPendingRequests(): Collection
    {
        return ApprovalRequest::with(['user', 'attendance'])
            ->where('status', 'pending')
            ->latest()
            ->get();
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
}
