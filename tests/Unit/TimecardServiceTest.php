<?php

namespace Tests\Unit;

use App\Models\Timecard;
use App\Models\User;
use App\Services\TimecardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimecardServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TimecardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TimecardService::class);
    }

    /**
     * 表示用フォーマットのテスト
     */
    public function testFormatTimecardForDisplay()
    {
        $user = User::factory()->create();
        $timecard = Timecard::factory()->create([
            'user_id' => $user->id,
            'clock_in' => Carbon::parse('2025-04-25 09:00:00'),
            'clock_out' => Carbon::parse('2025-04-25 18:00:00'),
            'break_start' => Carbon::parse('2025-04-25 12:00:00'),
            'break_end' => Carbon::parse('2025-04-25 13:00:00'),
            'overtime_minutes' => 60,
            'night_minutes' => 120
        ]);

        $result = $this->service->formatTimecardForDisplay($timecard);

        $this->assertEquals('09:00', $result['clock_in']);
        $this->assertEquals('18:00', $result['clock_out']);
        $this->assertEquals('1:00', $result['break_time']);
        $this->assertEquals('8:00', $result['work_time']);
        $this->assertEquals('1:00', $result['overtime']);
        $this->assertEquals('2:00', $result['night_work']);
    }

    /**
     * 残業時間計算のテスト
     */
    public function testCalculateOvertime()
    {
        $user = User::factory()->create();
        $timecard = Timecard::factory()->create([
            'user_id' => $user->id,
            'clock_in' => Carbon::parse('2025-04-25 09:00:00'),
            'clock_out' => Carbon::parse('2025-04-25 20:00:00'), // 11時間勤務
            'break_start' => Carbon::parse('2025-04-25 12:00:00'),
            'break_end' => Carbon::parse('2025-04-25 13:00:00') // 1時間休憩
        ]);

        $result = $this->service->calculateOvertime($timecard);

        // 11時間 - 1時間休憩 - 8時間基本 = 2時間残業
        $this->assertEquals(120, $result['overtime']);
    }

    /**
     * 深夜時間計算のテスト
     */
    public function testCalculateNightTime()
    {
        $user = User::factory()->create();
        $timecard = Timecard::factory()->create([
            'user_id' => $user->id,
            'clock_in' => Carbon::parse('2025-04-25 21:00:00'),
            'clock_out' => Carbon::parse('2025-04-26 02:00:00'), // 22時〜5時が深夜時間帯
            'break_start' => Carbon::parse('2025-04-25 23:00:00'),
            'break_end' => Carbon::parse('2025-04-26 00:30:00') // 1.5時間休憩（うち1時間が深夜時間帯）
        ]);

        $result = $this->service->calculateOvertime($timecard);

        // 22時〜5時 = 7時間
        // 休憩時間で1時間減る
        $this->assertEquals(6 * 60, $result['night']); // 6時間
    }
}
