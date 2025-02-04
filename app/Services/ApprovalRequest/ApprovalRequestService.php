<?php

namespace App\Services\ApprovalRequest;

use App\Models\ApprovalRequest;
use App\Models\Attendance;
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
     * @var ApprovalRequestFormatter
     */
    private $formatter;

    /**
     * コンストラクタ
     *
     * @param ApprovalRequestFormatter $formatter
     */
    public function __construct(ApprovalRequestFormatter $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * 指定したユーザーの申請一覧を取得
     *
     * @param int $userId ユーザーID
     * @return Collection フォーマット済みの申請一覧
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
     * ステータスでフィルタリングした申請一覧を取得
     *
     * @param string|null $status 取得したい申請のステータス（null or 'all'の場合は全件取得）
     * @param int $perPage 1ページあたりの表示件数
     * @return LengthAwarePaginator ページネーション済みの申請一覧
     */
    public function getFilteredRequests(?string $status = 'pending', int $perPage = 20): LengthAwarePaginator
    {
        $query = ApprovalRequest::with(['user', 'attendance', 'approver'])
            ->when($status && $status !== 'all', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest();

        return $query->paginate($perPage);
    }

    /**
     * 承認待ちの申請一覧を取得（管理者用）
     *
     * @return Collection フォーマット済みの承認待ち申請一覧
     */
    public function getPendingRequests(): Collection
    {
        $requests = $this->getFilteredRequests('pending')->items();
        return collect($requests)->map(fn($request) => $this->formatter->format($request));
    }

    /**
     * 新規申請を作成
     *
     * @param array $data
     * @return ApprovalRequest
     * @throws \Exception
     */
    public function createRequest(array $data): ApprovalRequest
    {
        try {
            return DB::transaction(function () use ($data) {
                // リクエストデータから申請を作成
                $request = ApprovalRequest::create($data);

                // 関連する勤怠データのステータスを更新
                $request->attendance->update([
                    'status' => 'pending_approval'
                ]);

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
     * @param ApprovalRequest $request
     * @return bool
     */
    public function approveRequest(ApprovalRequest $request): bool
    {
        try {
            return DB::transaction(function () use ($request) {
                // 申請を承認状態に更新
                $request->update([
                    'status' => 'approved'
                ]);

                // 勤怠データを修正内容で更新（statusの値を修正）
                $request->attendance->update([
                    'clock_in' => $request->after_clock_in,
                    'clock_out' => $request->after_clock_out,
                    'break_time' => $request->after_break_hours * 60,
                    'status' => 'left'
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
     * @param ApprovalRequest $request
     * @return bool
     */
    public function rejectRequest(ApprovalRequest $request): bool
    {
        try {
            return DB::transaction(function () use ($request) {
                // 申請を否認状態に更新
                $request->update([
                    'status' => 'rejected'
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
     * 勤怠データが修正申請可能か確認
     *
     * @param Attendance $attendance 確認対象の勤怠データ
     * @return bool 修正申請可能な場合はtrue
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
        return $this->formatter->formatAttendanceForRequest($attendance);
    }
}
