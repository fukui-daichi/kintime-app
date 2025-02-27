<?php

namespace Tests\Unit\Helpers;

use App\Constants\WorkTimeConstants;
use App\Exceptions\Timecard\InvalidWorkTimeException;
use App\Helpers\TimecardHelper;
use Carbon\Carbon;
use Tests\TestCase;

class TimecardHelperTest extends TestCase
{
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /** @test */
    public function 指定時刻が深夜時間帯かどうかを判定できる()
    {
        // 22時〜5時が深夜時間帯
        $this->assertTrue(TimecardHelper::isNightWorkHour(22));
        $this->assertTrue(TimecardHelper::isNightWorkHour(0));
        $this->assertTrue(TimecardHelper::isNightWorkHour(4));
        $this->assertFalse(TimecardHelper::isNightWorkHour(6));
        $this->assertFalse(TimecardHelper::isNightWorkHour(21));
    }

    /** @test */
    public function 深夜時間を計算できる()
    {
        // 通常勤務（深夜時間なし）
        $clockIn = Carbon::parse('2024-02-05 09:00:00');
        $clockOut = Carbon::parse('2024-02-05 18:00:00');
        $this->assertEquals(0, TimecardHelper::calculateNightWorkMinutes($clockIn, $clockOut));

        // 深夜勤務あり
        $clockIn = Carbon::parse('2024-02-05 20:00:00');
        $clockOut = Carbon::parse('2024-02-05 23:30:00');
        // 22:00から23:30までの90分
        $this->assertEquals(90, TimecardHelper::calculateNightWorkMinutes($clockIn, $clockOut));

        // 日をまたぐ深夜勤務
        $clockIn = Carbon::parse('2024-02-05 22:00:00');
        $clockOut = Carbon::parse('2024-02-06 01:00:00');
        // 22:00から01:00までの180分
        $this->assertEquals(180, TimecardHelper::calculateNightWorkMinutes($clockIn, $clockOut));
    }

    /** @test */
    public function 勤務時間を計算できる()
    {
        $clockIn = Carbon::parse('2024-02-05 09:00:00');
        $clockOut = Carbon::parse('2024-02-05 18:00:00');
        $breakTime = 60; // 1時間休憩

        $result = TimecardHelper::calculateWorkTimes($clockIn, $clockOut, $breakTime);

        // 9時間 - 1時間休憩 = 8時間（480分）
        $this->assertEquals(480, $result['actual_work_time']);
        // 所定労働時間は8時間なので残業なし
        $this->assertEquals(0, $result['overtime']);
        // 深夜時間なし
        $this->assertEquals(0, $result['night_work_time']);

        // 残業があるケース
        $clockOut = Carbon::parse('2024-02-05 19:00:00');
        $result = TimecardHelper::calculateWorkTimes($clockIn, $clockOut, $breakTime);
        // 10時間 - 1時間休憩 = 9時間（540分）
        $this->assertEquals(540, $result['actual_work_time']);
        // 所定労働時間8時間を超えた1時間（60分）が残業
        $this->assertEquals(60, $result['overtime']);
    }

    /** @test */
    public function 無効な勤務時間の場合は例外が発生する()
    {
        $this->expectException(InvalidWorkTimeException::class);

        // 退勤時刻が出勤時刻より前
        $clockIn = Carbon::parse('2024-02-05 18:00:00');
        $clockOut = Carbon::parse('2024-02-05 09:00:00');
        $breakTime = 60;

        TimecardHelper::calculateWorkTimes($clockIn, $clockOut, $breakTime);
    }
}
