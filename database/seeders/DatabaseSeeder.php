<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
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
            'name' => '人事部'
        ]);
        Department::factory()->create([
            'name' => '開発部'
        ]);

        // テストユーザー作成
        User::factory()->create([
            'employee_number' => '1001',
            'last_name' => '管理者',
            'first_name' => '太郎',
            'email' => 'admin@example.com',
            'department_id' => 1,
            'employment_type' => '正社員',
            'role' => 'admin',
            'is_active' => true,
            'joined_at' => '2020-04-01'
        ]);

        User::factory()->create([
            'employee_number' => '2001',
            'last_name' => '一般',
            'first_name' => '花子',
            'email' => 'user@example.com',
            'department_id' => 2,
            'employment_type' => '正社員',
            'role' => 'user',
            'is_active' => true,
            'joined_at' => '2021-04-01'
        ]);
    }
}
