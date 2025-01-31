<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // test@example.com ユーザーの勤怠データを作成
        $testUser = User::where('email', 'test@example.com')->first();

        if ($testUser) {
            // 過去20日分のデータを作成
            for ($i = 0; $i < 20; $i++) {
                $date = Carbon::now()->subDays($i);

                // 土日はスキップ
                if ($date->isWeekend()) {
                    continue;
                }

                // 9:00 ~ 10:00の間でランダムな出勤時間
                $clockIn = Carbon::create($date->year, $date->month, $date->day, 9)
                    ->addMinutes(rand(0, 60));

                // 17:00 ~ 19:00の間でランダムな退勤時間
                $clockOut = Carbon::create($date->year, $date->month, $date->day, 17)
                    ->addMinutes(rand(0, 120));

                // 実働時間の計算（分単位）
                // 出退勤時間の差分から休憩時間を引く
                $workMinutes = $clockIn->diffInMinutes($clockOut) - 60;

                Attendance::create([
                    'user_id' => $testUser->id,
                    'date' => $date->format('Y-m-d'),
                    'clock_in' => $clockIn->format('H:i:s'),
                    'clock_out' => $clockOut->format('H:i:s'),
                    'break_time' => 60,
                    'actual_work_time' => $workMinutes,
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
}
