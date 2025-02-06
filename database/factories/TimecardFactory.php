<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Timecard>
 */
class TimecardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // 9:00 ~ 10:00の間でランダムな出勤時間を生成
        $clockIn = Carbon::create(null, null, null, 9)
            ->addMinutes(fake()->numberBetween(0, 60));

        // 17:00 ~ 19:00の間でランダムな退勤時間を生成
        $clockOut = Carbon::create(null, null, null, 17)
            ->addMinutes(fake()->numberBetween(0, 120));

        return [
            'user_id' => User::factory(),
            'date' => fake()->dateTimeBetween('-1 month', 'now'),
            'clock_in' => $clockIn->format('H:i:s'),
            'clock_out' => $clockOut->format('H:i:s'),
            'break_time' => 60,
            'actual_work_time' => $clockOut->diffInMinutes($clockIn) - 60,
            'status' => 'left',
            'note' => fake()->optional()->sentence()
        ];
    }
}
