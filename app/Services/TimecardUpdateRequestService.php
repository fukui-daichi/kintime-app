<?php

namespace App\Services;

use App\Models\Timecard;
use App\Models\TimecardUpdateRequest;
use App\Models\User;
use App\Repositories\TimecardUpdateRequestRepository;
use App\Helpers\TimeHelper;

class TimecardUpdateRequestService
{
    private TimecardUpdateRequestRepository $repository;

    public function __construct(TimecardUpdateRequestRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * ダッシュボード表示用の未承認申請を取得
     */
    public function getPendingRequestsForDashboard(int $userId, int $limit = 5)
    {
        return TimecardUpdateRequest::where('user_id', $userId)
            ->pending()
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($request) {
                return [
                    'created_at' => $request->created_at->format('Y/m/d'),
                    'reason' => $request->reason,
                    'status' => $this->getStatusText($request->status)
                ];
            });
    }

    private function getStatusText(string $status): string
    {
        return match($status) {
            TimecardUpdateRequest::STATUS_PENDING => '承認待ち',
            TimecardUpdateRequest::STATUS_APPROVED => '承認済み',
            TimecardUpdateRequest::STATUS_REJECTED => '却下',
            default => $status
        };
    }

    /**
     * 申請作成処理
     */
    public function createRequest(array $validated, User $user): TimecardUpdateRequest
    {
        $data = $this->buildCreateData($validated, $user);
        return $this->repository->create($data);
    }

    /**
     * 申請承認処理
     */
    public function approveRequest(int $id, User $approver): bool
    {
        $request = $this->findRequest($id);
        if (!$request || !$this->canApprove($approver, $request)) {
            return false;
        }

        $approveData = $this->buildApproveData($approver);
        return $this->repository->update($request, $approveData);
    }

    /**
     * 申請データの加工
     */
    public function buildCreateData(array $validated, User $user): array
    {
        $timecard = Timecard::find($validated['timecard_id']);

        return [
            'user_id' => $user->id,
            'timecard_id' => $validated['timecard_id'],
            'original_clock_in' => $timecard->clock_in,
            'original_clock_out' => $timecard->clock_out,
            'original_break_start' => $timecard->break_start,
            'original_break_end' => $timecard->break_end,
            'corrected_clock_in' => $validated['corrected_clock_in'],
            'corrected_clock_out' => $validated['corrected_clock_out'],
            'corrected_break_start' => $validated['corrected_break_start'],
            'corrected_break_end' => $validated['corrected_break_end'],
            'status' => TimecardUpdateRequest::STATUS_PENDING,
            'reason' => $validated['reason']
        ];
    }

    /**
     * 承認時のデータ加工
     */
    public function buildApproveData(User $approver): array
    {
        return [
            'status' => TimecardUpdateRequest::STATUS_APPROVED,
            'approver_id' => $approver->id
        ];
    }

    /**
     * 承認権限判定
     */
    public function canApprove(User $user, TimecardUpdateRequest $request): bool
    {
        return $user->isAdmin() ||
            ($user->isManager() && $user->department_id === $request->user->department_id);
    }

    /**
     * 申請一覧表示用データを取得
     */
    public function getRequestData(User $user, \Illuminate\Http\Request $request): array
    {
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        return [
            'user' => $user,
            'requests' => $this->getUserRequests($user->id, $year, $month, 10),
            'year' => $year,
            'month' => $month,
            'yearOptions' => range(now()->year - 2, now()->year + 1)
        ];
    }

    /**
     * ユーザーの申請一覧取得（フォーマット済みデータ）
     */
    public function getUserRequests(int $userId, int $year, int $month, int $perPage = 10)
    {
        return $this->repository->getByUserId($userId, $year, $month, $perPage)
            ->through(function ($request) {
                return [
                    'id' => $request->id,
                    'created_at' => TimeHelper::formatJapaneseDate($request->created_at),
                    'before' => [
                        '出勤' => TimeHelper::formatTime($request->original_clock_in),
                        '退勤' => TimeHelper::formatTime($request->original_clock_out),
                        '休憩開始' => TimeHelper::formatTime($request->original_break_start),
                        '休憩終了' => TimeHelper::formatTime($request->original_break_end),
                    ],
                    'after' => [
                        '出勤' => TimeHelper::formatTime($request->corrected_clock_in),
                        '退勤' => TimeHelper::formatTime($request->corrected_clock_out),
                        '休憩開始' => TimeHelper::formatTime($request->corrected_break_start),
                        '休憩終了' => TimeHelper::formatTime($request->corrected_break_end),
                    ],
                    'status' => $request->status,
                    'approver_name' => $request->approver ? $request->approver->name : '-',
                    'reason' => $request->reason
                ];
            });
    }

    /**
     * IDで申請を検索
     */
    public function findRequest(int $id): ?TimecardUpdateRequest
    {
        return $this->repository->findById($id);
    }

}
