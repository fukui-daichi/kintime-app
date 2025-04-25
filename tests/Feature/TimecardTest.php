<?php

namespace Tests\Feature;

use App\Models\Timecard;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TimecardTest extends TestCase
{
    use DatabaseTransactions;

    public function test_clock_in_success()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('timecard.clock-in'));

        $response->assertRedirect();
        $response->assertSessionHas('status', '出勤打刻が完了しました');
        $this->assertDatabaseHas('timecards', [
            'user_id' => $user->id,
            'clock_in' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_clock_in_failure_when_already_clocked_in()
    {
        $user = User::factory()->create();
        Timecard::factory()->clockedIn()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->post(route('timecard.clock-in'));

        $response->assertRedirect();
        $response->assertSessionHas('error', '既に出勤打刻済みです');
    }

    public function test_clock_out_success()
    {
        $user = User::factory()->create();
        Timecard::factory()->clockedIn()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->post(route('timecard.clock-out'));

        $response->assertRedirect();
        $response->assertSessionHas('status', '退勤打刻が完了しました');
        $this->assertDatabaseHas('timecards', [
            'user_id' => $user->id,
            'clock_out' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_clock_out_failure_without_clock_in()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('timecard.clock-out'));

        $response->assertRedirect();
        $response->assertSessionHas('error', '退勤打刻ができません');
    }

    public function test_break_start_success()
    {
        $user = User::factory()->create();
        Timecard::factory()->clockedIn()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->post(route('timecard.break-start'));

        $response->assertRedirect();
        $response->assertSessionHas('status', '休憩開始打刻が完了しました');
        $this->assertDatabaseHas('timecards', [
            'user_id' => $user->id,
            'break_start' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_break_end_success()
    {
        $user = User::factory()->create();
        Timecard::factory()->onBreak()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->post(route('timecard.break-end'));

        $response->assertRedirect();
        $response->assertSessionHas('status', '休憩終了打刻が完了しました');
        $this->assertDatabaseHas('timecards', [
            'user_id' => $user->id,
            'break_end' => now()->format('Y-m-d H:i:s'),
        ]);
    }
}
