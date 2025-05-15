<?php

namespace App\Services;

use App\Models\Timecard;
use App\Models\TimecardUpdateRequest;
use App\Models\User;
use App\Repositories\TimecardUpdateRequestRepository;
use App\Helpers\DateHelper;
use App\Helpers\TimeHelper;

class TimecardUpdateRequestService
{
    protected TimecardUpdateRequestRepository $repository;

    public function __construct(TimecardUpdateRequestRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * リポジトリインスタンスを取得
     * @return TimecardUpdateRequestRepository
     */
    public function getRepository(): TimecardUpdateRequestRepository
    {
        return $this->repository;
    }

    // =============================================
    // 1. データ取得系
    // =============================================

    /**
     * ダッシュボード表示用の未承認申請を取得
     * @param int $userId ユーザーID
     * @param int $limit 取得件数
     * @return \Illuminate\Support\Collection フォーマット済み申請データコレクション
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
                    'created_at' => DateHelper::formatJapaneseDateWithoutYear($request->created_at),
                    'reason' => $request->reason,
                    'status' => $this->getStatusLabel($request->status)
                ];
            });
    }

    /**
     * 申請作成フォーム用データ取得
     */
    /**
     * 申請作成フォーム用データ取得
     * @param Timecard $timecard タイムカードオブジェクト
     * @param User $user ユーザーオブジェクト
     * @return array 表示用データ配列
     */
    public function getCreateFormData(Timecard $timecard, User $user): array
    {
        return [
            'user' => $user,
            'timecard' => $timecard,
            'formData' => [
                'date_formatted' => DateHelper::formatJapaneseDateWithYear($timecard->date),
                'date_iso' => DateHelper::formatToIsoDate($timecard->date),
                'clock_in' => TimeHelper::formatDateTimeToTime($timecard->clock_in),
                'clock_out' => TimeHelper::formatDateTimeToTime($timecard->clock_out),
                'break_start' => TimeHelper::formatDateTimeToTime($timecard->break_start),
                'break_end' => TimeHelper::formatDateTimeToTime($timecard->break_end)
            ]
        ];
    }

    /**
     * 申請一覧表示用データを取得
     * @param User $user ユーザーオブジェクト
     * @param \Illuminate\Http\Request $request リクエスト
     * @return array 表示用データ配列
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
     * @param int $userId ユーザーID
     * @param int $year 対象年
     * @param int $month 対象月
     * @param int $perPage ページネーション件数
     * @return \Illuminate\Pagination\LengthAwarePaginator ページネーションオブジェクト
     */
    public function getUserRequests(int $userId, int $year, int $month, int $perPage = 10)
    {
        return $this->repository->getByUserId($userId, $year, $month, $perPage)
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'created_at' => DateHelper::formatJapaneseDateWithoutYear($request->created_at),
                    'before' => [
                        '出勤' => TimeHelper::formatDateTimeToTime($request->original_clock_in),
                        '退勤' => TimeHelper::formatDateTimeToTime($request->original_clock_out),
                        '休憩開始' => TimeHelper::formatDateTimeToTime($request->original_break_start),
                        '休憩終了' => TimeHelper::formatDateTimeToTime($request->original_break_end),
                    ],
                    'after' => [
                        '出勤' => TimeHelper::formatDateTimeToTime($request->corrected_clock_in),
                        '退勤' => TimeHelper::formatDateTimeToTime($request->corrected_clock_out),
                        '休憩開始' => TimeHelper::formatDateTimeToTime($request->corrected_break_start),
                        '休憩終了' => TimeHelper::formatDateTimeToTime($request->corrected_break_end),
                    ],
                    'status' => $this->getStatusLabel($request->status),
                    'approver_name' => $request->approver?->name ?? '-',
                    'reason' => $request->reason
                ];
            });
    }

    /**
     * ステータスラベルを取得
     * @param string $status ステータス値
     * @return string 表示用ラベル
     */
    public function getStatusLabel(string $status): string
    {
        return match($status) {
            TimecardUpdateRequest::STATUS_PENDING => '承認待ち',
            TimecardUpdateRequest::STATUS_APPROVED => '承認済み',
            TimecardUpdateRequest::STATUS_REJECTED => '却下',
            default => $status
        };
    }

    // =============================================
    // 2. 申請処理系
    // =============================================

    /**
     * 申請作成処理
     * @param array $validated バリデーション済みデータ
     * @param User $user 申請ユーザー
     * @return TimecardUpdateRequest 作成された申請データ
     */
    public function createRequest(array $validated, User $user): TimecardUpdateRequest
    {
        $data = $this->buildCreateData($validated, $user);
        return $this->repository->create($data);
    }

    /**
     * 申請承認処理
     * @param int $id 申請ID
     * @param User $approver 承認者
     * @return bool 承認成功可否
     */
    public function approveRequest(int $id, User $approver): bool
    {
        $request = $this->repository->findById($id);
        if (!$request || !$this->canApprove($approver, $request)) {
            return false;
        }

        $approveData = $this->buildApproveData($approver);
        return $this->repository->update($request, $approveData);
    }

    // =============================================
    // 3. データ加工系
    // =============================================

    /**
     * 申請データの加工
     * @param array $validated バリデーション済みデータ
     * @param User $user 申請ユーザー
     * @return array データベース登録用フォーマット
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
     * @param User $approver 承認者
     * @return array 更新用データ
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
     * @param User $user 承認権限確認対象ユーザー
     * @param TimecardUpdateRequest $request 申請データ
     * @return bool 承認可能か否か
     */
    public function canApprove(User $user, TimecardUpdateRequest $request): bool
    {
        return $user->isAdmin() ||
            ($user->isManager() && $user->department_id === $request->user->department_id);
    }

    // =============================================
    // 4. 表示フォーマット系
    // =============================================



}
