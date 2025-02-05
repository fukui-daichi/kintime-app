<?php

namespace App\Helpers;

use Carbon\Carbon;

/**
 * 時間に関するフォーマット処理を提供するヘルパークラス
 */
class TimeFormatter
{
    /**
     * 分を時間表示形式（H:mm）に変換
     *
     * @param int|null $minutes 変換する分数（例: 90）
     * @return string|null フォーマットされた時間文字列（例: "1:30"）、
     *                    引数がnullの場合はnullを返す
     *
     * @example
     * TimeFormatter::minutesToTime(90)  // "1:30"を返す
     * TimeFormatter::minutesToTime(45)  // "0:45"を返す
     * TimeFormatter::minutesToTime(null) // nullを返す
     */
    public static function minutesToTime(?int $minutes): ?string
    {
        if ($minutes === null) {
            return null;
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return sprintf('%d:%02d', $hours, $remainingMinutes);
    }

    /**
     * 時間表示形式（H:mm）を分に変換
     *
     * @param string|null $time 時間文字列（例: "1:30"）
     * @return int|null 合計分数（例: 90）、
     *                 引数が空またはnullの場合はnullを返す
     *
     * @example
     * TimeFormatter::timeToMinutes("1:30") // 90を返す
     * TimeFormatter::timeToMinutes("0:45") // 45を返す
     * TimeFormatter::timeToMinutes(null)   // nullを返す
     */
    public static function timeToMinutes(?string $time): ?int
    {
        if (empty($time)) {
            return null;
        }

        [$hours, $minutes] = array_pad(explode(':', $time), 2, 0);
        return ($hours * 60) + $minutes;
    }

    /**
     * Carbonインスタンスを時刻フォーマット（H:i）に変換
     *
     * @param Carbon|null $time 変換対象のCarbonインスタンス
     * @return string|null フォーマットされた時刻文字列（例: "09:30"）、
     *                    引数がnullの場合はnullを返す
     *
     * @example
     * TimeFormatter::formatTime(Carbon::parse('2024-02-05 09:30:00')) // "09:30"を返す
     * TimeFormatter::formatTime(null) // nullを返す
     */
    public static function formatTime(?Carbon $time): ?string
    {
        return $time ? $time->format('H:i') : null;
    }

    /**
     * 日付を指定フォーマットに変換
     *
     * @param Carbon|null $date 変換対象のCarbonインスタンス
     * @param string $format 日付フォーマット（デフォルト: 'Y/m/d'）
     * @return string|null フォーマットされた日付文字列（例: "2024/02/05"）、
     *                    引数のdateがnullの場合はnullを返す
     *
     * @example
     * TimeFormatter::formatDate(Carbon::parse('2024-02-05')) // "2024/02/05"を返す
     * TimeFormatter::formatDate(Carbon::parse('2024-02-05'), 'Y年m月d日') // "2024年02月05日"を返す
     * TimeFormatter::formatDate(null) // nullを返す
     */
    public static function formatDate(?Carbon $date, string $format = 'Y/m/d'): ?string
    {
        return $date ? $date->format($format) : null;
    }

    /**
     * 分を2桁の時間形式（HH:mm）に変換
     *
     * @param int|null $minutes 変換する分数（例: 60）
     * @return string|null フォーマットされた時間文字列（例: "01:00"）、
     *                    引数がnullの場合はnullを返す
     *
     * @example
     * TimeFormatter::minutesToTwoDigitTime(60)  // "01:00"を返す
     * TimeFormatter::minutesToTwoDigitTime(90)  // "01:30"を返す
     * TimeFormatter::minutesToTwoDigitTime(null) // nullを返す
     */
    public static function minutesToTwoDigitTime(?int $minutes): ?string
    {
        if ($minutes === null) {
            return null;
        }

        return sprintf('%02d:%02d', floor($minutes / 60), $minutes % 60);
    }

    /**
     * 時刻と日付の文字列をデータベース保存用の日時形式（Y-m-d H:i:s）に変換
     *
     * @param string|null $timeString 時刻文字列（例: "09:30"）
     * @param string|null $dateString 日付文字列（例: "2024-02-05"）
     * @return string|null フォーマットされた日時文字列（例: "2024-02-05 09:30:00"）、
     *                    引数のいずれかがnullの場合はnullを返す
     *
     * @example
     * TimeFormatter::convertToDateTime("09:30", "2024-02-05") // "2024-02-05 09:30:00"を返す
     * TimeFormatter::convertToDateTime(null, "2024-02-05")   // nullを返す
     */
    public static function convertToDateTime(?string $timeString, ?string $dateString): ?string
    {
        if (empty($timeString) || empty($dateString)) {
            return null;
        }

        try {
            return Carbon::parse($dateString . ' ' . $timeString)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }
}
