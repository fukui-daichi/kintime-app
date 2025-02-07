<?php

namespace App\Constants;

/**
 * 申請関連の定数を管理するクラス
 */
class RequestConstants
{
    /**
     * 申請種別の定数
     */
    public const REQUEST_TYPE_TIMECARD = 'timecard';
    public const REQUEST_TYPE_PAID_VACATION = 'paid_vacation';

    /**
     * @var array<string, string> 申請種別の日本語表示
     */
    public const REQUEST_TYPES = [
        self::REQUEST_TYPE_TIMECARD => '勤怠修正',
        self::REQUEST_TYPE_PAID_VACATION => '有給休暇',
    ];

    /**
     * 申請状態の定数
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    /**
     * @var array<string, string> 申請状態の日本語表示
     */
    public const REQUEST_STATUSES = [
        self::STATUS_PENDING => '承認待ち',
        self::STATUS_APPROVED => '承認済み',
        self::STATUS_REJECTED => '否認',
    ];

    /**
     * @var array<string, string> 申請状態に対応するCSSクラス
     */
    public const STATUS_CLASSES = [
        self::STATUS_PENDING => 'bg-yellow-100 text-yellow-800',
        self::STATUS_APPROVED => 'bg-green-100 text-green-800',
        self::STATUS_REJECTED => 'bg-red-100 text-red-800',
    ];

    /**
     * 有給休暇種別の定数
     */
    public const VACATION_TYPE_FULL = 'full';
    public const VACATION_TYPE_AM = 'am';
    public const VACATION_TYPE_PM = 'pm';

    /**
     * @var array<string, string> 有給休暇種別の日本語表示
     */
    public const VACATION_TYPES = [
        self::VACATION_TYPE_FULL => '全休',
        self::VACATION_TYPE_AM => '午前半休',
        self::VACATION_TYPE_PM => '午後半休',
    ];

    /** @var int 1ページあたりの表示件数 */
    public const PER_PAGE = 20;

    /** @var array<string, string> 申請状態の定義（一覧表示用） */
    public const STATUS_LIST = [
        'all' => 'すべて',
        self::STATUS_PENDING => '承認待ち',
        self::STATUS_APPROVED => '承認済み',
        self::STATUS_REJECTED => '否認'
    ];

    /** @var string デフォルトのステータス */
    public const DEFAULT_STATUS = self::STATUS_PENDING;
}
