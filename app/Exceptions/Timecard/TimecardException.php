<?php

namespace App\Exceptions\Timecard;

/**
 * 勤怠管理に関する基底例外クラス
 */
class TimecardException extends \Exception
{
    /**
     * @var array エラーコンテキスト
     */
    protected array $context = [];

    /**
     * コンストラクタ
     *
     * @param string $message エラーメッセージ
     * @param array $context エラーコンテキスト
     * @param int $code エラーコード
     * @param \Throwable|null $previous 前の例外
     */
    public function __construct(
        string $message = "",
        array $context = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * エラーコンテキストを取得
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }
}

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
