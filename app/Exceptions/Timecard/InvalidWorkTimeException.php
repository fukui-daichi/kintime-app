<?php

namespace App\Exceptions\Timecard;

/**
 * 無効な勤務時間の例外
 */
class InvalidWorkTimeException extends TimecardException
{
    public function __construct(array $context = [], ?\Throwable $previous = null)
    {
        parent::__construct(
            '無効な勤務時間が指定されています。',
            $context,
            0,
            $previous
        );
    }
}
