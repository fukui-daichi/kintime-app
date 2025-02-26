<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Timecard;
use App\Constants\WorkTimeConstants;
use App\Helpers\TimecardHelper;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * 勤怠データのファクトリークラス
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Timecard>
 */
class TimecardFactory extends Factory
{
    /**
     * モデルのデフォルト状態を定義
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // 9:00 ~ 10:00の間でランダムな出勤時間を生成
        $clockIn = $this->createRandomTime(9, 0, 60);

        // 17:00 ~ 19:00の間でランダムな退勤時間を生成
        $clockOut = $this->createRandomTime(17, 0, 120);

        // 勤務時間の計算
        $breakTimeMinutes = WorkTimeConstants::DEFAULT_BREAK_MINUTES;
        $workMinutes = $clockIn->diffInMinutes($clockOut);
        $actualWorkMinutes = $workMinutes - $breakTimeMinutes;
        $overtimeMinutes = max(0, $actualWorkMinutes - WorkTimeConstants::REGULAR_WORK_MINUTES);

        // 深夜時間の計算
        $nightWorkMinutes = TimecardHelper::calculateNightWorkMinutes($clockIn, $clockOut);

        return [
            'user_id' => User::factory(),
            'date' => fake()->dateTimeBetween('-1 month', 'now'),
            'clock_in' => $clockIn->format('H:i:s'),
            'clock_out' => $clockOut->format('H:i:s'),
            'break_time' => $breakTimeMinutes,
            'actual_work_time' => $actualWorkMinutes,
            'overtime' => $overtimeMinutes,
            'night_work_time' => $nightWorkMinutes,
            'status' => Timecard::STATUS_LEFT,
            'note' => fake()->optional()->sentence()
        ];
    }
}
