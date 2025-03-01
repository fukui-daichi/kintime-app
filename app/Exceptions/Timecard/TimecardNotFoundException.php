<?php

namespace App\Exceptions\Timecard;

/**
 * 勤怠データが見つからない場合の例外
 */
class TimecardNotFoundException extends TimecardException
{
    public function __construct(array $context = [])
    {
        parent::__construct(
            '指定された勤怠データが見つかりません。',
            $context
        );
    }
}
