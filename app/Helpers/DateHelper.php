<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DateHelper
{
    /**
     * 現在の年月を返す
     */
    public static function getCurrentYearMonth(): array
    {
        $now = now();
        return [
            'year' => $now->year,
            'month' => $now->month,
        ];
    }

    /**
     * リクエストから年・月を解決（なければ現在年月を返す）
     */
    public static function resolveYearMonth(Request $request): array
    {
        $now = self::getCurrentYearMonth();
        $year = (int) $request->input('year', $now['year']);
        $month = (int) $request->input('month', $now['month']);
        return [
            'year' => $year,
            'month' => $month,
        ];
    }

    /**
     * 年月のバリデーション（1〜12月、年は2000〜2100の範囲）
     */
    public static function validateYearMonth(int $year, int $month): bool
    {
        return ($year >= 2000 && $year <= 2100) && ($month >= 1 && $month <= 12);
    }
}
