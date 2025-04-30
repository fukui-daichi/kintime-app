<?php

namespace App\Repositories;

use App\Models\TimecardUpdateRequest;
use Illuminate\Support\Collection;

class TimecardUpdateRequestRepository
{
    /**
     * 指定ユーザーの申請一覧を取得（ページネーション付き）
     */
    public function getByUserId(int $userId, int $perPage = 10)
    {
        return TimecardUpdateRequest::with(['timecard', 'approver'])
            ->where('user_id', $userId)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * IDで申請を取得
     */
    public function findById(int $id): ?TimecardUpdateRequest
    {
        return TimecardUpdateRequest::with(['timecard', 'approver'])->find($id);
    }

    /**
     * 新規申請を作成
     */
    public function create(array $data): TimecardUpdateRequest
    {
        return TimecardUpdateRequest::create($data);
    }

    /**
     * 申請を更新
     */
    public function update(TimecardUpdateRequest $request, array $data): bool
    {
        return $request->update($data);
    }
}
