<?php

namespace App\Services\ApprovalRequest;

use App\Models\ApprovalRequest;
use App\Models\Attendance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApprovalRequestService
{
    private $formatter;

    public function __construct(ApprovalRequestFormatter $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * ユーザーの申請一覧を取得
     */
    public function getUserRequests(int $userId): Collection
    {
        $requests = ApprovalRequest::with(['attendance', 'approver'])
            ->where('user_id', $userId)
            ->latest()
            ->get();

        return $requests->map(fn($request) => $this->formatter->format($request));
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

        return $requests->map(fn($request) => $this->formatter->format($request));
    }

    /**
     * 申請を作成
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
     */
    public function canRequestModification(Attendance $attendance): bool
    {
        return !$attendance->hasPendingRequest() &&
               $attendance->status !== 'pending_approval';
    }
}
