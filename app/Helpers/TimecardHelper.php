<?php

namespace App\Helpers;

use App\Constants\WorkTimeConstants;
use App\Exceptions\Timecard\InvalidWorkTimeException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * 勤怠管理に関する共通ヘルパー機能を提供するクラス
 */
class TimecardHelper
{
    /**
     * 深夜時間を計算（22時〜5時の間の勤務時間）
     *
     * @param Carbon $clockIn 出勤時間
     * @param Carbon $clockOut 退勤時間
     * @return int 深夜時間（分）
     */
    public static function calculateNightWorkMinutes(Carbon $clockIn, Carbon $clockOut): int
    {
        $nightWorkMinutes = 0;
        $currentTime = $clockIn->copy();
        $endTime = $clockOut->copy();

        // 日をまたぐ場合の調整
        if ($clockOut->lt($clockIn)) {
            $endTime->addDay();
        }

        while ($currentTime->lt($endTime)) {
            $hour = (int)$currentTime->format('H');
            if (self::isNightWorkHour($hour)) {
                $nightWorkMinutes++;
            }
            $currentTime->addMinute();
        }

        return $nightWorkMinutes;
    }

    /**
     * 指定時刻が深夜時間帯かどうかを判定
     *
     * @param int $hour 時間（0-23）
     * @return bool 深夜時間帯であればtrue
     */
    public static function isNightWorkHour(int $hour): bool
    {
        return $hour >= WorkTimeConstants::NIGHT_WORK_START_HOUR ||
               $hour < WorkTimeConstants::NIGHT_WORK_END_HOUR;
    }

    /**
     * 勤務時間を計算
     *
     * @param Carbon $clockIn 出勤時間
     * @param Carbon $clockOut 退勤時間
     * @param int $breakTime 休憩時間（分）
     * @return array 計算結果の配列（actual_work_time, overtime, night_work_time）
     * @throws InvalidWorkTimeException 無効な勤務時間の場合
     */
    public static function calculateWorkTimes(Carbon $clockIn, Carbon $clockOut, int $breakTime): array
    {
        try {
            // 退勤時刻が出勤時刻より前の場合
            if ($clockOut->lt($clockIn)) {
                throw new InvalidWorkTimeException([
                    'clock_in' => $clockIn->toTimeString(),
                    'clock_out' => $clockOut->toTimeString()
                ]);
            }

            $workMinutes = $clockIn->diffInMinutes($clockOut);
            $actualWorkMinutes = $workMinutes - $breakTime;
            $overtimeMinutes = max(0, $actualWorkMinutes - WorkTimeConstants::REGULAR_WORK_MINUTES);

            // 計算結果のログ（デバッグ用）
            self::logWorkTimeCalculation($clockIn, $clockOut, $workMinutes, $breakTime, $actualWorkMinutes);

            return [
                'actual_work_time' => $actualWorkMinutes,
                'overtime' => $overtimeMinutes,
                'night_work_time' => self::calculateNightWorkMinutes($clockIn, $clockOut),
            ];
        } catch (InvalidWorkTimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            $context = [
                'clock_in' => $clockIn->toTimeString(),
                'clock_out' => $clockOut->toTimeString(),
                'break_time' => $breakTime
            ];
            throw new InvalidWorkTimeException($context, $e);
        }
    }

    /**
     * 勤務時間計算のログを記録
     *
     * @param Carbon $clockIn 出勤時間
     * @param Carbon $clockOut 退勤時間
     * @param int $workMinutes 総勤務時間（分）
     * @param int $breakTime 休憩時間（分）
     * @param int $actualWorkMinutes 実労働時間（分）
     * @return void
     */
    private static function logWorkTimeCalculation(
        Carbon $clockIn,
        Carbon $clockOut,
        int $workMinutes,
        int $breakTime,
        int $actualWorkMinutes
    ): void {
        Log::debug('勤務時間計算詳細', [
            'clock_in' => $clockIn->format('H:i'),
            'clock_out' => $clockOut->format('H:i'),
            'total_minutes' => $workMinutes,
            'break_time' => $breakTime,
            'actual_minutes' => $actualWorkMinutes,
        ]);
    }
}
