<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use App\Models\Timecard;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 部署データ作成
        Department::factory()->create([
            'name' => '管理部'
        ]);
        Department::factory()->create([
            'name' => '人事部'
        ]);
        Department::factory()->create([
            'name' => '開発部'
        ]);

        // テストユーザー作成 (合計5人)
        // 管理者1人 (管理部)
        User::factory()->create([
            'employee_number' => '1001',
            'last_name' => 'システム',
            'first_name' => '管理者',
            'email' => 'admin@example.com',
            'department_id' => 1, // 管理部
            'employment_type' => '正社員',
            'role' => 'admin',
            'is_active' => true,
            'joined_at' => '2020-04-01'
        ]);

        // マネージャー2人 (人事部と開発部)
        User::factory()->create([
            'employee_number' => '2001',
            'last_name' => '人事',
            'first_name' => '太郎',
            'email' => 'manager1@example.com',
            'department_id' => 2, // 人事部
            'employment_type' => '正社員',
            'role' => 'manager',
            'is_active' => true,
            'joined_at' => '2021-04-01'
        ]);

        User::factory()->create([
            'employee_number' => '2002',
            'last_name' => '開発',
            'first_name' => '次郎',
            'email' => 'manager2@example.com',
            'department_id' => 3, // 開発部
            'employment_type' => '正社員',
            'role' => 'manager',
            'is_active' => true,
            'joined_at' => '2021-04-01'
        ]);

        // 一般ユーザー2人 (人事部と開発部)
        User::factory()->create([
            'employee_number' => '3001',
            'last_name' => '人事',
            'first_name' => '花子',
            'email' => 'user1@example.com',
            'department_id' => 2, // 人事部
            'employment_type' => '正社員',
            'role' => 'user',
            'is_active' => true,
            'joined_at' => '2022-04-01'
        ]);

        User::factory()->create([
            'employee_number' => '3002',
            'last_name' => '開発',
            'first_name' => '健太',
            'email' => 'user2@example.com',
            'department_id' => 3, // 開発部
            'employment_type' => '正社員',
            'role' => 'user',
            'is_active' => true,
            'joined_at' => '2022-04-01'
        ]);

        // 一般ユーザーと上長ユーザーに対して直近180日分のタイムカードを生成
        $users = User::where('role', '!=', 'admin')->get();
        $today = now();
        $startDate = $today->copy()->subDays(180);

        foreach ($users as $user) {
            $currentDate = $startDate->copy();

            while ($currentDate <= $today) {
                if (!$currentDate->isWeekend()) {
                    // 出勤時間（9時〜15時の間でランダム）
                    $clockInHour = rand(9, 15);
                    $clockIn = $currentDate->copy()->setTime($clockInHour, rand(0, 59), 0);

                    // 退勤時間（出勤時間 + 8時間以上、最大23時まで）
                    $minClockOut = $clockIn->copy()->addHours(8);
                    $maxClockOut = $currentDate->copy()->setTime(23, 59, 59);
                    $clockOut = $minClockOut->addMinutes(rand(0, $maxClockOut->diffInMinutes($minClockOut)));

                    // 休憩時間（1時間）
                    $breakStart = $clockIn->copy()->addHours(rand(1, 6));
                    $breakEnd = $breakStart->copy()->addHours(1);

                    // ユーザーごとに異なる勤務パターンを割り当て
                    if ($user->id % 4 === 0) {
                        // 通常勤務（残業なし）
                        Timecard::factory()->create([
                            'user_id' => $user->id,
                            'date' => $currentDate,
                            'clock_in' => $clockIn,
                            'clock_out' => $clockOut,
                            'break_start' => $breakStart,
                            'break_end' => $breakEnd,
                            'status' => 'approved'
                        ]);
                    } elseif ($user->id % 4 === 1) {
                        // 残業あり
                        Timecard::factory()->withOvertime()->create([
                            'user_id' => $user->id,
                            'date' => $currentDate,
                            'status' => 'approved'
                        ]);
                    } elseif ($user->id % 4 === 2) {
                        // 深夜勤務あり
                        Timecard::factory()->withNightWork()->create([
                            'user_id' => $user->id,
                            'date' => $currentDate,
                            'status' => 'approved'
                        ]);
                    } else {
                        // 深夜勤務+残業あり
                        Timecard::factory()->withOvertimeAndNightWork()->create([
                            'user_id' => $user->id,
                            'date' => $currentDate,
                            'status' => 'approved'
                        ]);
                    }
                }
                $currentDate->addDay();
            }
        }
    }
}
