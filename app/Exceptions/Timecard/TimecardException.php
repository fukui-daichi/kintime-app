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
