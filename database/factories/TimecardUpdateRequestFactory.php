<?php

namespace Database\Factories;

use App\Models\Timecard;
use Illuminate\Database\Eloquent\Factories\Factory;

class TimecardUpdateRequestFactory extends Factory
{
    public function definition()
    {
        $timecard = Timecard::inRandomOrder()->first();

        return [
            'user_id' => $timecard->user_id,
            'timecard_id' => $timecard->id,
            'original_clock_in' => $timecard->clock_in,
            'original_clock_out' => $timecard->clock_out,
            'original_break_start' => $timecard->break_start,
            'original_break_end' => $timecard->break_end,
            'corrected_clock_in' => $timecard->clock_in->addMinutes(rand(-30, 30)),
            'corrected_clock_out' => $timecard->clock_out->addMinutes(rand(-30, 30)),
            'corrected_break_start' => $timecard->break_start->addMinutes(rand(-15, 15)),
            'corrected_break_end' => $timecard->break_end->addMinutes(rand(-15, 15)),
            'status' => 'pending',
            'reason' => $this->faker->randomElement([
                '打刻ミス',
                '打刻忘れ',
                'システム不具合による誤記録'
            ]),
        ];
    }
}
