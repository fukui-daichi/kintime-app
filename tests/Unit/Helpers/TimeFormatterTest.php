<?php

namespace Tests\Unit\Helpers;

use App\Helpers\TimeFormatter;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class TimeFormatterTest extends TestCase
{
    /** @test */
    public function 分を時間表示形式に変換できる()
    {
        $this->assertEquals('01:30', TimeFormatter::minutesToTime(90));
        $this->assertEquals('00:45', TimeFormatter::minutesToTime(45));
        $this->assertEquals('02:00', TimeFormatter::minutesToTime(120));
        $this->assertNull(TimeFormatter::minutesToTime(null));
    }

    /** @test */
    public function 時間表示形式を分に変換できる()
    {
        $this->assertEquals(90, TimeFormatter::timeToMinutes('1:30'));
        $this->assertEquals(45, TimeFormatter::timeToMinutes('0:45'));
        $this->assertEquals(120, TimeFormatter::timeToMinutes('2:00'));
        $this->assertNull(TimeFormatter::timeToMinutes(null));
        $this->assertNull(TimeFormatter::timeToMinutes(''));
    }

    /** @test */
    public function Carbonインスタンスを時刻フォーマットに変換できる()
    {
        $time = Carbon::parse('2024-02-05 09:30:00');
        $this->assertEquals('09:30', TimeFormatter::formatTime($time));
        $this->assertNull(TimeFormatter::formatTime(null));
    }

    /** @test */
    public function 日付を指定フォーマットに変換できる()
    {
        $date = Carbon::parse('2024-02-05');
        $this->assertEquals('2024/02/05', TimeFormatter::formatDate($date));
        $this->assertEquals('2024年02月05日', TimeFormatter::formatDate($date, 'Y年m月d日'));
        $this->assertNull(TimeFormatter::formatDate(null));
    }

    /** @test */
    public function 分を2桁の時間形式に変換できる()
    {
        $this->assertEquals('01:00', TimeFormatter::minutesToTwoDigitTime(60));
        $this->assertEquals('01:30', TimeFormatter::minutesToTwoDigitTime(90));
        $this->assertNull(TimeFormatter::minutesToTwoDigitTime(null));
    }

    /** @test */
    public function 日時データをHH_mm形式に変換できる()
    {
        $this->assertEquals('09:30', TimeFormatter::toHourMinute('2025-02-20 09:30:00'));
        $this->assertEquals('09:30', TimeFormatter::toHourMinute('09:30:00'));
        $this->assertNull(TimeFormatter::toHourMinute(null));
        $this->assertNull(TimeFormatter::toHourMinute(''));
    }
}
