<?php

namespace App\Repositories\Eloquent;

use App\Models\Timecard;
use App\Repositories\Interfaces\TimecardRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TimecardRepository implements TimecardRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function findById(int $id): ?Timecard
    {
        return Timecard::find($id);
    }

    /**
     * @inheritDoc
     */
    public function getTodayTimecard(int $userId): ?Timecard
    {
        return Timecard::where('user_id', $userId)
            ->where('date', Carbon::now()->toDateString())
            ->first();
    }

    /**
     * @inheritDoc
     */
    public function getMonthlyTimecards(int $userId, Carbon $startDate, Carbon $endDate): Collection
    {
        return Timecard::where('user_id', $userId)
            ->whereBetween('date', [
                $startDate->toDateString(),
                $endDate->toDateString()
            ])
            ->orderBy('date')
            ->get();
    }

    /**
     * @inheritDoc
     */
    public function create(array $data): Timecard
    {
        return Timecard::create($data);
    }

    /**
     * @inheritDoc
     */
    public function update(Timecard $timecard, array $data): bool
    {
        return $timecard->update($data);
    }

    /**
     * @inheritDoc
     */
    public function getTodayWorkingTimecard(int $userId): ?Timecard
    {
        return Timecard::where('user_id', $userId)
            ->where('date', Carbon::now()->toDateString())
            ->where('status', 'working')
            ->first();
    }

    /**
     * @inheritDoc
     */
    public function hasTodayTimecard(int $userId): bool
    {
        return Timecard::where('user_id', $userId)
            ->where('date', Carbon::now()->toDateString())
            ->exists();
    }
}
