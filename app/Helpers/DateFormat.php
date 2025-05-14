<?php

namespace App\Helpers;

use Carbon\Carbon;

class DateFormat
{
    /**
     * 日本語形式で日付をフォーマット
     */
    public static function formatJapaneseDate(\DateTime $date): string
    {
        $weekday = ['日', '月', '火', '水', '木', '金', '土'][$date->format('w')];
        return $date->format("Y年m月d日（{$weekday}）");
    }

    /**
     * 日付を指定フォーマットで表示
     */
    public static function formatDate(\DateTime $date, string $format = 'Y-m-d'): string
    {
        return $date->format($format);
    }
}
