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

        // 一般ユーザーと上長ユーザーに対して直近180日分のタイムカードを生成（本日は含まない）
        $users = User::where('role', '!=', 'admin')->get();
        $yesterday = now()->subDay();
        $startDate = $yesterday->copy()->subDays(180);

        foreach ($users as $user) {
            $currentDate = $startDate->copy();
            $workDays = collect();

            // まず勤務日リストを作成
            while ($currentDate <= $yesterday) {
                if (!$currentDate->isWeekend()) {
                    $workDays->push($currentDate->copy());
                }
                $currentDate->addDay();
            }

            // 勤務日を半分に分割
            $half = (int)($workDays->count() / 2);
            $dayCount = 0;

            foreach ($workDays as $workDate) {
                $dayCount++;
                if ($dayCount <= $half) {
                    // 前半は通常勤務
                    Timecard::factory()->standardWork()->create([
                        'user_id' => $user->id,
                        'date' => $workDate,
                        'status' => 'approved'
                    ]);
                } else {
                    // 後半は残業あり
                    Timecard::factory()->withOvertime()->create([
                        'user_id' => $user->id,
                        'date' => $workDate,
                        'status' => 'approved'
                    ]);
                }
            }
        }
    }
}
