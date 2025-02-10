<?php

namespace App\Repositories\Eloquent;

use App\Models\Request;
use App\Constants\RequestConstants;
use App\Repositories\Interfaces\RequestRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class RequestRepository implements RequestRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function findById(int $id): ?Request
    {
        return Request::find($id);
    }

    /**
     * @inheritDoc
     */
    public function create(array $data): Request
    {
        return Request::create($data);
    }

    /**
     * @inheritDoc
     */
    public function update(Request $request, array $data): bool
    {
        return $request->update($data);
    }

    /**
     * @inheritDoc
     */
    public function getPaginatedUserRequests(int $userId, ?string $status = null): LengthAwarePaginator
    {
        $query = Request::with(['user', 'approver'])
            ->where('user_id', $userId)
            ->latest();

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        return $query->paginate(RequestConstants::PER_PAGE);
    }

    /**
     * @inheritDoc
     */
    public function getPaginatedAllRequests(?string $status = null): LengthAwarePaginator
    {
        $query = Request::with(['user', 'approver'])
            ->latest();

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        return $query->paginate(RequestConstants::PER_PAGE);
    }

    /**
     * @inheritDoc
     */
    public function getPendingRequestByTimecardId(int $timecardId): ?Request
    {
        return Request::where('timecard_id', $timecardId)
            ->where('status', RequestConstants::STATUS_PENDING)
            ->first();
    }

    /**
     * @inheritDoc
     */
    public function countPendingRequests(int $userId): int
    {
        return Request::where('user_id', $userId)
            ->where('status', RequestConstants::STATUS_PENDING)
            ->count();
    }
}
