<?php

namespace App\Constants;

/**
 * 勤務時間に関する定数を管理するクラス
 */
class WorkTimeConstants
{
    /** 所定労働時間（分）：8時間 = 480分 */
    public const REGULAR_WORK_MINUTES = 480;

    /** 深夜時間帯開始時刻（時） */
    public const NIGHT_WORK_START_HOUR = 22;

    /** 深夜時間帯終了時刻（時） */
    public const NIGHT_WORK_END_HOUR = 5;

    /** デフォルトの休憩時間（分） */
    public const DEFAULT_BREAK_MINUTES = 60;
}
