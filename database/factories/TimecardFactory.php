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

    public function standardWork()
    {
        return $this->state(function (array $attributes) {
            $date = Carbon::parse($attributes['date']);
            return [
                'clock_in' => $date->copy()->setTime(10, 0, 0),
                'clock_out' => $date->copy()->setTime(19, 0, 0),
                'break_start' => $date->copy()->setTime(14, 0, 0),
                'break_end' => $date->copy()->setTime(15, 0, 0),
                'overtime_minutes' => 0,
                'night_minutes' => 0,
            ];
        });
    }

    public function withOvertime()
    {
        return $this->state(function (array $attributes) {
            $date = Carbon::parse($attributes['date']);
            return [
                'clock_in' => $date->copy()->setTime(10, 0, 0),
                'clock_out' => $date->copy()->setTime(20, 0, 0),
                'break_start' => $date->copy()->setTime(14, 0, 0),
                'break_end' => $date->copy()->setTime(15, 0, 0),
                'overtime_minutes' => 60,
                'night_minutes' => 0,
            ];
        });
    }
}
