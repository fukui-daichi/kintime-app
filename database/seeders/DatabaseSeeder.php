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
            'last_name' => 'システム',
            'first_name' => '管理者',
            'email' => 'admin@example.com',
            'department_id' => 1,
            'employment_type' => '正社員',
            'role' => 'admin',
            'is_active' => true,
            'joined_at' => '2020-04-01'
        ]);

        User::factory()->create([
            'employee_number' => '3001',
            'last_name' => '人事',
            'first_name' => '太郎',
            'email' => 'manager@example.com',
            'department_id' => 1,
            'employment_type' => '正社員',
            'role' => 'manager',
            'is_active' => true,
            'joined_at' => '2020-04-01'
        ]);

        User::factory()->create([
            'employee_number' => '2001',
            'last_name' => '開発',
            'first_name' => '花子',
            'email' => 'user1@example.com',
            'department_id' => 2,
            'employment_type' => '正社員',
            'role' => 'user',
            'is_active' => true,
            'joined_at' => '2021-04-01'
        ]);

        User::factory()->create([
            'employee_number' => '2002',
            'last_name' => '開発',
            'first_name' => '健太',
            'email' => 'user2@example.com',
            'department_id' => 2,
            'employment_type' => '契約社員',
            'role' => 'user',
            'is_active' => true,
            'joined_at' => '2022-04-01'
        ]);

        User::factory()->create([
            'employee_number' => '2003',
            'last_name' => '開発',
            'first_name' => 'さくら',
            'email' => 'user3@example.com',
            'department_id' => 2,
            'employment_type' => '正社員',
            'role' => 'user',
            'is_active' => true,
            'joined_at' => '2023-04-01'
        ]);
    }
}
