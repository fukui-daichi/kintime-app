<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use DateTime;

class DateHelper
{
    // =============================================
    // 年月日取得処理
    // =============================================

    /**
     * 現在の日付を配列で取得
     * @return array ['year' => 2025, 'month' => 5, 'day' => 14]
     */
    public static function getCurrentDate(): array
    {
        $now = now();
        return [
            'year' => $now->year,
            'month' => $now->month,
            'day' => $now->day
        ];
    }

    /**
     * 現在の日付を日本語形式で取得
     * @return string "M月D日（dd）"形式の文字列
     */
    public static function getCurrentJapaneseDate(): string
    {
        return now()->locale('ja')->isoFormat('M月D日（dd）');
    }

    /**
     * 現在の年月を配列で取得
     * @return array ['year' => 2025, 'month' => 5]
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
     * @param Request $request HTTPリクエスト
     * @return array ['year' => 2025, 'month' => 5]
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
     * @param int $year 年
     * @param int $month 月
     * @return bool 有効な年月の場合true
     */
    public static function validateYearMonth(int $year, int $month): bool
    {
        return ($year >= 2000 && $year <= 2100) && ($month >= 1 && $month <= 12);
    }

    // =============================================
    // 日付フォーマット変換処理
    // =============================================

    /**
     * Carbonオブジェクトを日本語形式の日付文字列に変換
     * @param Carbon|null $date 変換対象のCarbonオブジェクト
     * @return string "Y年M月D日（ddd）"形式の文字列
     */
    public static function formatToJapaneseDateString(Carbon $date = null): string
    {
        return ($date ?? now())->locale('ja')->isoFormat('YYYY年M月D日（ddd）');
    }

    /**
     * DateTimeオブジェクトを日本語形式の日付文字列に変換
     * @param DateTime $date 変換対象のDateTimeオブジェクト
     * @return string "Y年m月d日（曜日）"形式の文字列
     */
    public static function formatDateTimeToJapaneseDate(DateTime $date): string
    {
        $weekday = ['日', '月', '火', '水', '木', '金', '土'][$date->format('w')];
        return $date->format("Y年m月d日（{$weekday}）");
    }

    /**
     * Carbonオブジェクトを日本語形式の日付文字列に変換（年を含む）
     * @param Carbon|null $date 変換対象のCarbonオブジェクト
     * @return string "Y年M月D日（ddd）"形式の文字列
     */
    public static function formatJapaneseDateWithYear(Carbon $date = null): string
    {
        return ($date ?? now())->locale('ja')->isoFormat('YYYY年M月D日（ddd）');
    }

    /**
     * Carbonオブジェクトを日本語形式の日付文字列に変換（年を含まない）
     * @param Carbon $date 変換対象のCarbonオブジェクト
     * @return string "M月D日（dd）"形式の文字列
     */
    public static function formatJapaneseDateWithoutYear(Carbon $date): string
    {
        return $date->locale('ja')->isoFormat('M月D日（dd）');
    }

    // =============================================
    // 日付リスト生成処理
    // =============================================

    /**
     * 指定年月の日付リストを生成
     * @param int $year 年
     * @param int $month 月
     * @return array ['04-01', '04-02', ...]形式の配列
     */
    public static function generateMonthDateList(int $year, int $month): array
    {
        $days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $dates = [];
        for ($d = 1; $d <= $days; $d++) {
            $dates[] = sprintf('%02d-%02d', $month, $d);
        }
        return $dates;
    }

    /**
     * 年選択肢を生成
     * @param int $minYear 最小年
     * @param int $maxYear 最大年
     * @return array [minYear, ..., maxYear+1]の配列
     */
    public static function getYearOptions(int $minYear, int $maxYear): array
    {
        return range($minYear, $maxYear + 1);
    }
}
