<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApprovalRequest>
 */
class ApprovalRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // 既存の勤怠データを取得（ない場合は新規作成）
        $attendance = Attendance::factory()->create();

        // 修正前の時間を勤怠データから取得
        $beforeClockIn = $attendance->clock_in ? Carbon::parse($attendance->clock_in) : null;
        $beforeClockOut = $attendance->clock_out ? Carbon::parse($attendance->clock_out) : null;

        // 修正後の時間を生成（±30分の範囲でランダム）
        $afterClockIn = $beforeClockIn
            ? $beforeClockIn->copy()->addMinutes(fake()->numberBetween(-30, 30))
            : Carbon::now()->setTime(9, 0);
        $afterClockOut = $beforeClockOut
            ? $beforeClockOut->copy()->addMinutes(fake()->numberBetween(-30, 30))
            : Carbon::now()->setTime(18, 0);

        return [
            'user_id' => $attendance->user_id,
            'approver_id' => User::factory()->state(['user_type' => 'admin']),
            'attendance_id' => $attendance->id,
            'request_type' => fake()->randomElement(['time_correction', 'break_time_modification']),
            'before_clock_in' => $beforeClockIn,
            'before_clock_out' => $beforeClockOut,
            'before_break_hours' => 1.00,
            'after_clock_in' => $afterClockIn,
            'after_clock_out' => $afterClockOut,
            'after_break_hours' => fake()->randomElement([0.5, 1.0, 1.5]),
            'status' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'reason' => fake()->sentence(),
            'comment' => fake()->optional(0.7)->sentence(),
        ];
    }

    /**
     * 承認待ち状態の申請を生成
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'comment' => null,
        ]);
    }

    /**
     * 承認済み状態の申請を生成
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'comment' => fake()->sentence(),
        ]);
    }

    /**
     * 否認状態の申請を生成
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'comment' => fake()->sentence(),
        ]);
    }
}
