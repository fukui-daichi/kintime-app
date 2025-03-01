<?php

namespace App\Exceptions\Timecard;

/**
 * 出勤記録が存在しない場合の例外
 */
class ClockInNotFoundException extends TimecardException
{
    public function __construct(array $context = [])
    {
        parent::__construct(
            '本日の出勤記録が見つかりません。',
            $context
        );
    }
}
