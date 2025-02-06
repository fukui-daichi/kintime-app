<?php

namespace App\Constants;

/**
 * 申請関連の定数を管理するクラス
 */
class ApprovalRequestConstants
{
    /**
     * @var array<string, string> 申請種別の日本語表示
     */
    public const REQUEST_TYPES = [
        'time_correction' => '時刻修正',
        'break_time_modification' => '休憩時間修正',
    ];

    /**
     * @var array<string, string> 申請状態の日本語表示
     */
    public const REQUEST_STATUSES = [
        'pending' => '承認待ち',
        'approved' => '承認済み',
        'rejected' => '否認',
    ];

    /**
     * @var array<string, string> 申請状態に対応するCSSクラス
     */
    public const STATUS_CLASSES = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'approved' => 'bg-green-100 text-green-800',
        'rejected' => 'bg-red-100 text-red-800',
    ];

    /** @var int 1ページあたりの表示件数 */
    public const PER_PAGE = 20;

    /** @var array<string, string> 申請状態の定義 */
    public const STATUS_LIST = [
        'all' => 'すべて',
        'pending' => '承認待ち',
        'approved' => '承認済み',
        'rejected' => '否認'
    ];

    /** @var string デフォルトのステータス */
    public const DEFAULT_STATUS = 'pending';
}
