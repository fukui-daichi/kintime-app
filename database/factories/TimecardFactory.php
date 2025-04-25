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
}
