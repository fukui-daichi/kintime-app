<?php

namespace App\Exceptions\Timecard;

/**
 * 重複打刻に関する例外
 */
class DuplicateClockInException extends TimecardException
{
    public function __construct(array $context = [])
    {
        parent::__construct(
            '本日はすでに出勤打刻されています。',
            $context
        );
    }
}
