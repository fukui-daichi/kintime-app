<?php

namespace App\Exceptions\Timecard;

/**
 * 勤怠データの作成に失敗した場合の例外
 */
class TimecardCreateException extends TimecardException
{
    public function __construct(array $context = [], ?\Throwable $previous = null)
    {
        parent::__construct(
            '勤怠データの作成に失敗しました。',
            $context,
            0,
            $previous
        );
    }
}
