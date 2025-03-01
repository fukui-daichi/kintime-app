<?php

namespace App\Exceptions\Timecard;

/**
 * 勤怠データの更新に失敗した場合の例外
 */
class TimecardUpdateException extends TimecardException
{
    public function __construct(array $context = [], ?\Throwable $previous = null)
    {
        parent::__construct(
            '勤怠データの更新に失敗しました。',
            $context,
            0,
            $previous
        );
    }
}
