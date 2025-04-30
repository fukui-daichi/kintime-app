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
     * ユーザーの申請一覧取得
     */
    public function getUserRequests(int $userId, int $perPage = 10)
    {
        return $this->repository->getByUserId($userId, $perPage);
    }

    /**
     * IDで申請を検索
     */
    public function findRequest(int $id): ?TimecardUpdateRequest
    {
        return $this->repository->findById($id);
    }

    /**
     * フォーム表示用データを準備
     */
    public function prepareFormData(Timecard $timecard): array
    {
        return [
            'clock_in' => TimeHelper::formatTime($timecard->clock_in),
            'clock_out' => TimeHelper::formatTime($timecard->clock_out),
            'break_start' => TimeHelper::formatTime($timecard->break_start),
            'break_end' => TimeHelper::formatTime($timecard->break_end),
            'date_formatted' => TimeHelper::formatJapaneseDate($timecard->date),
            'date_iso' => $timecard->date->format('Y-m-d')
        ];
    }
}
