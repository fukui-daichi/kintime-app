<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Timecard;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class RequestFactory extends Factory
{
    public function definition(): array
    {
        // 既存の勤怠データを取得（ない場合は新規作成）
        $timecard = Timecard::factory()->create();
        $targetDate = $timecard->date;

        return [
            'user_id' => $timecard->user_id,
            'approver_id' => User::factory()->state(['user_type' => 'admin']),
            'request_type' => $this->faker->randomElement(['timecard', 'paid_vacation']),
            'target_date' => $targetDate,
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'reason' => $this->faker->sentence(),
            'comment' => $this->faker->optional(0.7)->sentence(),
            // 勤怠修正用データ
            'before_clock_in' => $timecard->clock_in,
            'before_clock_out' => $timecard->clock_out,
            'after_clock_in' => Carbon::parse($timecard->clock_in)->addMinutes($this->faker->numberBetween(-30, 30)),
            'after_clock_out' => Carbon::parse($timecard->clock_out)->addMinutes($this->faker->numberBetween(-30, 30)),
            'before_break_time' => 60,
            'after_break_time' => $this->faker->randomElement([30, 60, 90]),
            // 有給休暇用データ
            'vacation_type' => $this->faker->randomElement(['full', 'am', 'pm']),
            'approved_at' => $this->faker->optional(0.3)->dateTime(),
        ];
    }

    // 承認待ち状態の申請を生成
    public function pending(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
                'approved_at' => null,
                'comment' => null,
            ];
        });
    }

    // 承認済み状態の申請を生成
    public function approved(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'approved',
                'approved_at' => now(),
                'comment' => $this->faker->sentence(),
            ];
        });
    }

    // 否認状態の申請を生成
    public function rejected(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'rejected',
                'approved_at' => now(),
                'comment' => $this->faker->sentence(),
            ];
        });
    }
}
