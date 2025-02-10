<?php

namespace App\Repositories\Interfaces;

use App\Models\Request;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface RequestRepositoryInterface
{
    /**
     * 申請をIDで取得
     *
     * @param int $id
     * @return Request|null
     */
    public function findById(int $id): ?Request;

    /**
     * 申請を作成
     *
     * @param array $data
     * @return Request
     */
    public function create(array $data): Request;

    /**
     * 申請を更新
     *
     * @param Request $request
     * @param array $data
     * @return bool
     */
    public function update(Request $request, array $data): bool;

    /**
     * ユーザーの申請一覧を取得（ページネーション付き）
     *
     * @param int $userId
     * @param string|null $status
     * @return LengthAwarePaginator
     */
    public function getPaginatedUserRequests(int $userId, ?string $status = null): LengthAwarePaginator;

    /**
     * 全ての申請一覧を取得（ページネーション付き）
     *
     * @param string|null $status
     * @return LengthAwarePaginator
     */
    public function getPaginatedAllRequests(?string $status = null): LengthAwarePaginator;

    /**
     * 特定の勤怠データに対する承認待ちの申請を取得
     *
     * @param int $timecardId
     * @return Request|null
     */
    public function getPendingRequestByTimecardId(int $timecardId): ?Request;

    /**
     * 特定のユーザーの承認待ち申請数を取得
     *
     * @param int $userId
     * @return int
     */
    public function countPendingRequests(int $userId): int;
}
