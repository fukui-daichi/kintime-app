<?php

namespace Tests\Unit;

use App\Helpers\TimeHelper;
use Tests\TestCase;

class TimeHelperTest extends TestCase
{
    /**
     * 分数をHH:MM形式に変換するテスト
     */
    public function testFormatMinutesToTime()
    {
        $this->assertEquals('0:00', TimeHelper::formatMinutesToTime(0));
        $this->assertEquals('0:01', TimeHelper::formatMinutesToTime(1));
        $this->assertEquals('1:00', TimeHelper::formatMinutesToTime(60));
        $this->assertEquals('1:30', TimeHelper::formatMinutesToTime(90));
        $this->assertEquals('8:45', TimeHelper::formatMinutesToTime(525));
    }

    /**
     * 日時を指定フォーマットで表示するテスト
     */
    public function testFormatDateTime()
    {
        $datetime = new \DateTime('2025-04-25 09:15:30');

        $this->assertEquals('09:15', TimeHelper::formatDateTime($datetime));
        $this->assertEquals('09:15:30', TimeHelper::formatDateTime($datetime, 'H:i:s'));
        $this->assertEquals('2025-04-25', TimeHelper::formatDateTime($datetime, 'Y-m-d'));
    }
}
