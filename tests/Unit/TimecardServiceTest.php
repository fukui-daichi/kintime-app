<?php

namespace Tests\Unit;

use App\Models\Timecard;
use App\Models\User;
use App\Services\TimecardService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TimecardServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected TimecardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TimecardService::class);
    }

    public function test_clock_in_success()
    {
        $user = User::factory()->create();

        $timecard = $this->service->clockIn($user);

        $this->assertNotNull($timecard->clock_in);
        $this->assertEquals($user->id, $timecard->user_id);
    }

    public function test_clock_in_failure_when_already_clocked_in()
    {
        $user = User::factory()->create();
        $timecard = Timecard::factory()->clockedIn()->create(['user_id' => $user->id]);

        // デバッグ用: 作成されたタイムカードを確認
        $this->assertNotNull($timecard->clock_in);
        $this->assertEquals($user->id, $timecard->user_id);

        // デバッグ用: リポジトリから取得したタイムカードを確認
        $fetched = app(\App\Repositories\TimecardRepository::class)->getTodayTimecard($user->id);
        $this->assertNotNull($fetched);
        $this->assertNotNull($fetched->clock_in);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('既に出勤打刻済みです');
        $this->service->clockIn($user);
    }

    public function test_clock_out_success()
    {
        $user = User::factory()->create();
        Timecard::factory()->clockedIn()->create(['user_id' => $user->id]);

        $timecard = $this->service->clockOut($user);

        $this->assertNotNull($timecard->clock_out);
    }

    public function test_clock_out_failure_without_clock_in()
    {
        $user = User::factory()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('退勤打刻ができません');
        $this->service->clockOut($user);
    }

    public function test_start_break_success()
    {
        $user = User::factory()->create();
        Timecard::factory()->clockedIn()->create(['user_id' => $user->id]);

        $timecard = $this->service->startBreak($user);

        $this->assertNotNull($timecard->break_start);
    }

    public function test_start_break_failure_without_clock_in()
    {
        $user = User::factory()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('休憩開始打刻ができません');
        $this->service->startBreak($user);
    }

    public function test_end_break_success()
    {
        $user = User::factory()->create();
        Timecard::factory()->onBreak()->create(['user_id' => $user->id]);

        $timecard = $this->service->endBreak($user);

        $this->assertNotNull($timecard->break_end);
    }

    public function test_end_break_failure_without_break_start()
    {
        $user = User::factory()->create();
        Timecard::factory()->clockedIn()->create(['user_id' => $user->id]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('休憩終了打刻ができません');
        $this->service->endBreak($user);
    }

    public function test_get_timecard_button_status()
    {
        $user = User::factory()->create();

        // 初期状態（出勤前）
        $status = $this->service->getTimecardButtonStatus($user->id);
        $this->assertFalse($status['clockIn']['disabled']);
        $this->assertTrue($status['clockOut']['disabled']);
        $this->assertTrue($status['breakStart']['disabled']);
        $this->assertTrue($status['breakEnd']['disabled']);

        // 出勤後
        Timecard::factory()->clockedIn()->create(['user_id' => $user->id]);
        $status = $this->service->getTimecardButtonStatus($user->id);
        $this->assertTrue($status['clockIn']['disabled']);
        $this->assertFalse($status['clockOut']['disabled']);
        $this->assertFalse($status['breakStart']['disabled']);
        $this->assertTrue($status['breakEnd']['disabled']);

        // 休憩中
        Timecard::where('user_id', $user->id)->delete();
        $timecard = Timecard::factory()->onBreak()->create(['user_id' => $user->id]);
        $this->assertNotNull($timecard->break_start);
        $this->assertNull($timecard->break_end);

        $status = $this->service->getTimecardButtonStatus($user->id);
        $this->assertTrue($status['clockIn']['disabled']);
        $this->assertFalse($status['clockOut']['disabled']);
        $this->assertTrue($status['breakStart']['disabled']);
        $this->assertFalse($status['breakEnd']['disabled']);

        // 退勤後
        Timecard::where('user_id', $user->id)->delete();
        Timecard::factory()->clockedOut()->create(['user_id' => $user->id]);
        $status = $this->service->getTimecardButtonStatus($user->id);
        $this->assertTrue($status['clockIn']['disabled']);
        $this->assertTrue($status['clockOut']['disabled']);
        $this->assertTrue($status['breakStart']['disabled']);
        $this->assertTrue($status['breakEnd']['disabled']);
    }

    public function test_calculate_overtime_no_overtime()
    {
        $user = User::factory()->create();
        $timecard = Timecard::factory()->create([
            'user_id' => $user->id,
            'clock_in' => now()->setHour(9)->setMinute(0),
            'clock_out' => now()->setHour(17)->setMinute(0),
            'break_start' => now()->setHour(12)->setMinute(0),
            'break_end' => now()->setHour(13)->setMinute(0)
        ]);

        $result = $this->service->calculateOvertime($timecard);
        $this->assertEquals(0, $result['overtime']);
        $this->assertEquals(0, $result['night']);
    }

    public function test_calculate_overtime_with_overtime()
    {
        $user = User::factory()->create();
        $timecard = Timecard::factory()->create([
            'user_id' => $user->id,
            'clock_in' => now()->setHour(9)->setMinute(0),
            'clock_out' => now()->setHour(19)->setMinute(0),
            'break_start' => now()->setHour(12)->setMinute(0),
            'break_end' => now()->setHour(13)->setMinute(0)
        ]);

        $result = $this->service->calculateOvertime($timecard);
        $this->assertEquals(60, $result['overtime']); // 1時間の残業 (9時間実働 - 8時間基本)
        $this->assertEquals(0, $result['night']);
    }

    public function test_calculate_night_time()
    {
        $user = User::factory()->create();
        $timecard = Timecard::factory()->create([
            'user_id' => $user->id,
            'clock_in' => now()->setHour(20)->setMinute(0),
            'clock_out' => now()->addDay()->setHour(2)->setMinute(0),
            'break_start' => null,
            'break_end' => null
        ]);

        $result = $this->service->calculateOvertime($timecard);
        $this->assertEquals(240, $result['night']); // 22:00-2:00 = 4時間
    }

    public function test_calculate_overtime_with_night_crossing()
    {
        $user = User::factory()->create();
        $timecard = Timecard::factory()->create([
            'user_id' => $user->id,
            'clock_in' => now()->setHour(21)->setMinute(0),
            'clock_out' => now()->addDay()->setHour(6)->setMinute(0),
            'break_start' => now()->setHour(23)->setMinute(0),
            'break_end' => now()->addDay()->setHour(0)->setMinute(30)
        ]);

        $result = $this->service->calculateOvertime($timecard);
        $this->assertEquals(0, $result['overtime']); // 実働7.5時間 - 基本8時間 = -0.5時間 → 0
        $this->assertEquals(330, $result['night']); // 22:00-5:00 (休憩時間0:30-1:00を除く)
    }
}
