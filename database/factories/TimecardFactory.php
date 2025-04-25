<?php

namespace Database\Factories;

use App\Models\Timecard;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class TimecardFactory extends Factory
{
    protected $model = Timecard::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'date' => Carbon::today(),
            'clock_in' => null,
            'clock_out' => null,
            'break_start' => null,
            'break_end' => null,
            'status' => 'pending',
            'notes' => $this->faker->sentence(),
        ];
    }

    public function clockedIn()
    {
        return $this->state(function (array $attributes) {
            return [
                'clock_in' => now()->subHours(9),
            ];
        });
    }

    public function clockedOut()
    {
        return $this->state(function (array $attributes) {
            return [
                'date' => \Carbon\Carbon::today(),
                'clock_in' => now()->subHours(9),
                'clock_out' => now(),
            ];
        });
    }

    public function onBreak()
    {
        return $this->state(function (array $attributes) {
            return [
                'date' => \Carbon\Carbon::today(),
                'clock_in' => now()->subHours(9),
                'clock_out' => null,
                'break_start' => now()->subMinutes(30),
                'break_end' => null,
                'status' => 'on_break',
            ];
        });
    }

    public function finishedBreak()
    {
        return $this->state(function (array $attributes) {
            return [
                'clock_in' => now()->subHours(9),
                'break_start' => now()->subHours(1),
                'break_end' => now()->subMinutes(30),
            ];
        });
    }

    public function withOvertime()
    {
        return $this->state(function (array $attributes) {
            return [
                'clock_in' => now()->subHours(10),
                'clock_out' => now(),
                'break_start' => now()->subHours(5),
                'break_end' => now()->subHours(4),
                'overtime_minutes' => 60,
                'night_minutes' => 0,
            ];
        });
    }

    public function withNightWork()
    {
        return $this->state(function (array $attributes) {
            return [
                'clock_in' => Carbon::today()->setHour(22),
                'clock_out' => Carbon::tomorrow()->setHour(2),
                'overtime_minutes' => 0,
                'night_minutes' => 240,
            ];
        });
    }

    public function withOvertimeAndNightWork()
    {
        return $this->state(function (array $attributes) {
            return [
                'clock_in' => Carbon::today()->setHour(20),
                'clock_out' => Carbon::tomorrow()->setHour(3),
                'break_start' => Carbon::today()->setHour(23),
                'break_end' => Carbon::tomorrow()->setHour(0)->setMinute(30),
                'overtime_minutes' => 60,
                'night_minutes' => 330,
            ];
        });
    }
}
