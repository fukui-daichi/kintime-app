<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Timecard;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class TimecardSeeder extends Seeder
{
    /** 所定労働時間（分） */
    private const REGULAR_WORK_MINUTES = 480; // 8時間 = 480分

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 一般ユーザーを全て取得
        $users = User::where('user_type', 'user')->get();

        foreach ($users as $user) {
            // 1年分のデータを作成
            for ($i = 0; $i < 365; $i++) {
                $date = Carbon::now()->subDays($i);

                // 土日はスキップ
                if ($date->isWeekend()) {
                    continue;
                }

                $pattern = rand(1, 100);
                $clockIn = Carbon::create($date->year, $date->month, $date->day, 9)
                    ->addMinutes(rand(0, 60));

                if ($pattern <= 10) {
                    // 遅い退勤パターン（22時以降）
                    $clockOut = Carbon::create($date->year, $date->month, $date->day, 22)
                        ->addMinutes(rand(0, 120));
                } elseif ($pattern <= 30) {
                    // 残業パターン（19時前後）
                    $clockOut = Carbon::create($date->year, $date->month, $date->day, 19)
                        ->addMinutes(rand(0, 120));
                } else {
                    // 通常パターン（17時前後）
                    $clockOut = Carbon::create($date->year, $date->month, $date->day, 17)
                        ->addMinutes(rand(0, 120));
                }

                // 勤務時間の計算（分単位）
                $breakTimeMinutes = 60; // 休憩時間は60分固定
                $workMinutes = $clockIn->diffInMinutes($clockOut);
                $actualWorkMinutes = $workMinutes - $breakTimeMinutes;
                $overtimeMinutes = max(0, $actualWorkMinutes - self::REGULAR_WORK_MINUTES);

                // 深夜時間の計算（22時〜5時）
                $nightWorkMinutes = $this->calculateNightWorkMinutes($clockIn, $clockOut);

                Timecard::create([
                    'user_id' => $user->id,
                    'date' => $date->format('Y-m-d'),
                    'clock_in' => $clockIn->format('H:i:s'),
                    'clock_out' => $clockOut->format('H:i:s'),
                    'break_time' => $breakTimeMinutes,
                    'actual_work_time' => $actualWorkMinutes,
                    'overtime' => $overtimeMinutes,
                    'night_work_time' => $nightWorkMinutes,
                    'status' => 'left',
                ]);
            }

            // 本日分のデータ作成
            if (!$user->Timecards()->whereDate('date', Carbon::today())->exists()) {
                Timecard::create([
                    'user_id' => $user->id,
                    'date' => Carbon::today(),
                    'clock_in' => '09:00:00',
                    'status' => 'working',
                    'break_time' => 60,
                ]);
            }
        }
    }

    /**
     * 深夜時間を計算（22時〜5時の間の勤務時間）
     *
     * @param Carbon $clockIn
     * @param Carbon $clockOut
     * @return int 深夜時間（分）
     */
    private function calculateNightWorkMinutes(Carbon $clockIn, Carbon $clockOut): int
    {
        $nightWorkMinutes = 0;
        $currentTime = $clockIn->copy();

        while ($currentTime < $clockOut) {
            $hour = (int)$currentTime->format('H');
            if ($hour >= 22 || $hour < 5) {
                $nightWorkMinutes++;
            }
            $currentTime->addMinute();
        }

        return $nightWorkMinutes;
    }
}
