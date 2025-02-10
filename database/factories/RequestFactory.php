<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Timecard;
use App\Models\Request;
use App\Constants\RequestConstants;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class RequestFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Request::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // ランダムな勤怠データを取得
        $timecard = Timecard::inRandomOrder()->first();
        $targetDate = $timecard ? $timecard->date : now();

        return [
            'user_id' => $timecard ? $timecard->user_id : User::factory(),
            'approver_id' => User::where('user_type', 'admin')->first()?->id,
            'timecard_id' => $timecard?->id,
            'request_type' => RequestConstants::REQUEST_TYPE_TIMECARD,
            'target_date' => $targetDate,
            'status' => RequestConstants::STATUS_PENDING,
            'reason' => fake()->sentence(),
            'comment' => null,
            'before_clock_in' => $timecard?->clock_in,
            'before_clock_out' => $timecard?->clock_out,
            'after_clock_in' => $timecard ? Carbon::parse($timecard->clock_in)->addMinutes(rand(-30, 30))->format('H:i:s') : null,
            'after_clock_out' => $timecard ? Carbon::parse($timecard->clock_out)->addMinutes(rand(-30, 30))->format('H:i:s') : null,
            'before_break_time' => $timecard?->break_time,
            'after_break_time' => 60,
            'vacation_type' => null,
            'approved_at' => null,
        ];
    }

    /**
     * 有給休暇申請の状態を設定
     */
    public function paidVacation(): static
    {
        return $this->state(function (array $attributes) {
            // 有給休暇申請の場合は打刻関連のデータをクリア
            return [
                'request_type' => RequestConstants::REQUEST_TYPE_PAID_VACATION,
                'before_clock_in' => null,
                'before_clock_out' => null,
                'after_clock_in' => null,
                'after_clock_out' => null,
                'before_break_time' => null,
                'after_break_time' => null,
                'vacation_type' => collect(['full', 'am', 'pm'])->random(),
            ];
        });
    }

    /**
     * 承認済み状態を設定
     */
    public function approved(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => RequestConstants::STATUS_APPROVED,
                'approved_at' => now(),
                'comment' => fake()->sentence(),
            ];
        });
    }

    /**
     * 否認状態を設定
     */
    public function rejected(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => RequestConstants::STATUS_REJECTED,
                'approved_at' => now(),
                'comment' => fake()->sentence(),
            ];
        });
    }
}
