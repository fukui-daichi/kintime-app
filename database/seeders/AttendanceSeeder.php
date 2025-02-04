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

                // 以下のコードは既存と同じ
                $pattern = rand(1, 100);
                $clockIn = Carbon::create($date->year, $date->month, $date->day, 9)
                    ->addMinutes(rand(0, 60));

                // パターンに応じた退勤時間設定（既存コードと同じ）
                if ($pattern <= 10) {
                    $clockOut = Carbon::create($date->year, $date->month, $date->day, 22)
                        ->addMinutes(rand(0, 120));
                } elseif ($pattern <= 30) {
                    $clockOut = Carbon::create($date->year, $date->month, $date->day, 19)
                        ->addMinutes(rand(0, 120));
                } else {
                    $clockOut = Carbon::create($date->year, $date->month, $date->day, 17)
                        ->addMinutes(rand(0, 120));
                }

                // 時間計算（既存コードと同じ）
                $workMinutes = $clockIn->diffInMinutes($clockOut) - 60;
                $overtimeMinutes = max(0, $workMinutes - self::REGULAR_WORK_MINUTES);
                $nightWorkMinutes = $this->calculateNightWorkMinutes($clockIn, $clockOut);

                // 勤怠データ作成
                Attendance::create([
                    'user_id' => $user->id,
                    'date' => $date->format('Y-m-d'),
                    'clock_in' => $clockIn->format('H:i:s'),
                    'clock_out' => $clockOut->format('H:i:s'),
                    'break_time' => 60,
                    'actual_work_time' => $workMinutes,
                    'overtime' => $overtimeMinutes,
                    'night_work_time' => $nightWorkMinutes,
                    'status' => 'left',
                    'note' => null
                ]);
            }

            // 本日分のデータ作成
            if (!$user->attendances()->whereDate('date', Carbon::today())->exists()) {
                Attendance::create([
                    'user_id' => $user->id,
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
     * @return int 深夜時間（分）
     */
    private function calculateNightWorkMinutes(Carbon $clockIn, Carbon $clockOut): int
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
