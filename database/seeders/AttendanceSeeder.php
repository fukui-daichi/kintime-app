<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    /** 所定労働時間（分） */
    private const REGULAR_WORK_MINUTES = 480; // 8時間 = 480分
    /** 深夜時間帯開始時刻 */
    private const NIGHT_WORK_START = 22;
    /** 深夜時間帯終了時刻 */
    private const NIGHT_WORK_END = 5;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // test@example.com ユーザーの勤怠データを作成
        $testUser = User::where('email', 'test@example.com')->first();

        if ($testUser) {
            // 1年分のデータを作成
            for ($i = 0; $i < 365; $i++) {
                $date = Carbon::now()->subDays($i);

                // 土日はスキップ
                if ($date->isWeekend()) {
                    continue;
                }

                // 20%の確率で残業パターン（19:00以降）、10%の確率で深夜残業パターン（22:00以降）
                $pattern = rand(1, 100);

                // 9:00 ~ 10:00の間でランダムな出勤時間
                $clockIn = Carbon::create($date->year, $date->month, $date->day, 9)
                    ->addMinutes(rand(0, 60));

                // パターンに応じて退勤時間を設定
                if ($pattern <= 10) {
                    // 深夜残業パターン（22:00〜24:00）
                    $clockOut = Carbon::create($date->year, $date->month, $date->day, 22)
                        ->addMinutes(rand(0, 120));
                } elseif ($pattern <= 30) {
                    // 残業パターン（19:00〜21:00）
                    $clockOut = Carbon::create($date->year, $date->month, $date->day, 19)
                        ->addMinutes(rand(0, 120));
                } else {
                    // 通常パターン（17:00〜19:00）
                    $clockOut = Carbon::create($date->year, $date->month, $date->day, 17)
                        ->addMinutes(rand(0, 120));
                }

                // 実労働時間の計算（分単位）
                $workMinutes = $clockIn->diffInMinutes($clockOut) - 60; // 休憩時間1時間を引く

                // 残業時間の計算（分単位）
                $overtime = max(0, $workMinutes - self::REGULAR_WORK_MINUTES);

                // 深夜時間の計算
                $nightWorkMinutes = $this->calculateNightWorkMinutes($clockIn, $clockOut);

                Attendance::create([
                    'user_id' => $testUser->id,
                    'date' => $date->format('Y-m-d'),
                    'clock_in' => $clockIn->format('H:i:s'),
                    'clock_out' => $clockOut->format('H:i:s'),
                    'break_time' => 60,
                    'actual_work_time' => $workMinutes,
                    'overtime' => $overtime > 0 ? round($overtime / 60, 2) : null,
                    'night_work_time' => $nightWorkMinutes > 0 ? round($nightWorkMinutes / 60, 2) : null,
                    'status' => 'left',
                    'note' => null
                ]);
            }

            // 本日分の勤怠データ（出勤のみ）を作成
            if (!$testUser->attendances()->whereDate('date', Carbon::today())->exists()) {
                Attendance::create([
                    'user_id' => $testUser->id,
                    'date' => Carbon::today(),
                    'clock_in' => '09:00:00',
                    'status' => 'working',
                ]);
            }
        }
    }

    /**
     * 深夜時間を計算（22時〜5時の間の勤務時間）
     *
     * @param Carbon $clockIn 出勤時刻
     * @param Carbon $clockOut 退勤時刻
     * @return float 深夜時間（分）
     */
    private function calculateNightWorkMinutes(Carbon $clockIn, Carbon $clockOut): float
    {
        $nightWorkMinutes = 0;
        $currentTime = $clockIn->copy();

        while ($currentTime < $clockOut) {
            $hour = (int)$currentTime->format('H');

            // 22時〜24時、または0時〜5時の場合は深夜時間として計算
            if ($hour >= self::NIGHT_WORK_START || $hour < self::NIGHT_WORK_END) {
                $nightWorkMinutes++;
            }

            $currentTime->addMinute();
        }

        return $nightWorkMinutes;
    }
}
