<?php

namespace App\Repositories\Interfaces;

use App\Models\Timecard;
use Carbon\Carbon;
use Illuminate\Support\Collection;

interface TimecardRepositoryInterface
{
    /**
     * 勤怠データをIDで取得
     *
     * @param int $id
     * @return Timecard|null
     */
    public function findById(int $id): ?Timecard;

    /**
     * 本日の勤怠データを取得
     *
     * @param int $userId
     * @return Timecard|null
     */
    public function getTodayTimecard(int $userId): ?Timecard;

    /**
     * 指定月の勤怠データを取得
     *
     * @param int $userId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return Collection
     */
    public function getMonthlyTimecards(int $userId, Carbon $startDate, Carbon $endDate): Collection;

    /**
     * 勤怠データを作成
     *
     * @param array $data
     * @return Timecard
     */
    public function create(array $data): Timecard;

    /**
     * 勤怠データを更新
     *
     * @param Timecard $timecard
     * @param array $data
     * @return bool
     */
    public function update(Timecard $timecard, array $data): bool;

    /**
     * 本日の作業中の勤怠データを取得
     *
     * @param int $userId
     * @return Timecard|null
     */
    public function getTodayWorkingTimecard(int $userId): ?Timecard;

    /**
     * 本日の勤怠記録が存在するか確認
     *
     * @param int $userId
     * @return bool
     */
    public function hasTodayTimecard(int $userId): bool;

    /**
     * 指定ユーザーの指定日のタイムカードを取得
     *
     * @param int $userId ユーザーID
     * @param Carbon $date 日付
     * @return Timecard|null
     */
    public function getTimecardByDate(int $userId, Carbon $date): ?Timecard;
}
