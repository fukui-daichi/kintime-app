<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Timecard;
use App\Constants\WorkTimeConstants;
use App\Helpers\TimecardHelper;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

/**
 * 勤怠データのシードを行うクラス
 */
class TimecardSeeder extends Seeder
{
    /**
     * 勤怠データのシードを実行
     *
     * @return void
     */
    public function run(): void
    {
        // 一般ユーザーを全て取得
        $users = User::where('user_type', User::TYPE_USER)->get();

        foreach ($users as $user) {
            // 1年分のデータを作成
            for ($i = 0; $i < 365; $i++) {
                $date = Carbon::now()->subDays($i);

                // 土日はスキップ
                if ($date->isWeekend()) {
                    continue;
                }

                $this->createTimecardForDate($user->id, $date);
            }

            // 本日分のデータがなければ作成
            if (!$user->timecards()->whereDate('date', Carbon::today())->exists()) {
                Timecard::create([
                    'user_id' => $user->id,
                    'date' => Carbon::today(),
                    'clock_in' => '09:00:00',
                    'status' => Timecard::STATUS_WORKING,
                    'break_time' => WorkTimeConstants::DEFAULT_BREAK_MINUTES,
                ]);
            }
        }
    }

    /**
     * 指定日付の勤怠データを作成
     *
     * @param int $userId ユーザーID
     * @param Carbon $date 日付
     * @return void
     */
    private function createTimecardForDate(int $userId, Carbon $date): void
    {
        // 勤務パターンをランダムに選択（1-100の乱数）
        $pattern = rand(1, 100);

        // 出勤時刻は9:00〜10:00の間でランダム
        $clockIn = Carbon::create($date->year, $date->month, $date->day, 9)
            ->addMinutes(rand(0, 60));

        // 勤務パターンに応じて退勤時刻を設定
        $clockOut = $this->determineClockOutTime($date, $pattern);

        // 勤務時間の計算
        $breakTimeMinutes = WorkTimeConstants::DEFAULT_BREAK_MINUTES;
        $workMinutes = $clockIn->diffInMinutes($clockOut);
        $actualWorkMinutes = $workMinutes - $breakTimeMinutes;
        $overtimeMinutes = max(0, $actualWorkMinutes - WorkTimeConstants::REGULAR_WORK_MINUTES);

        // 深夜時間の計算
        $nightWorkMinutes = TimecardHelper::calculateNightWorkMinutes($clockIn, $clockOut);

        // 勤怠データ作成
        Timecard::create([
            'user_id' => $userId,
            'date' => $date->format('Y-m-d'),
            'clock_in' => $clockIn->format('H:i:s'),
            'clock_out' => $clockOut->format('H:i:s'),
            'break_time' => $breakTimeMinutes,
            'actual_work_time' => $actualWorkMinutes,
            'overtime' => $overtimeMinutes,
            'night_work_time' => $nightWorkMinutes,
            'status' => Timecard::STATUS_LEFT,
        ]);
    }

    /**
     * 勤務パターンに応じた退勤時間を決定
     *
     * @param Carbon $date 基準日
     * @param int $pattern 勤務パターン（1-100）
     * @return Carbon 退勤時間
     */
    private function determineClockOutTime(Carbon $date, int $pattern): Carbon
    {
        if ($pattern <= 10) {
            // 遅い退勤パターン（22時以降）
            return Carbon::create($date->year, $date->month, $date->day, 22)
                ->addMinutes(rand(0, 120));
        } elseif ($pattern <= 30) {
            // 残業パターン（19時前後）
            return Carbon::create($date->year, $date->month, $date->day, 19)
                ->addMinutes(rand(0, 120));
        } else {
            // 通常パターン（17時前後）
            return Carbon::create($date->year, $date->month, $date->day, 17)
                ->addMinutes(rand(0, 120));
        }
    }
}
