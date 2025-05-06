<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 管理者1人 (管理部)
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

        // マネージャー2人 (人事部と開発部)
        User::factory()->create([
            'employee_number' => '2001',
            'last_name' => '人事',
            'first_name' => '太郎',
            'email' => 'manager1@example.com',
            'department_id' => 2,
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
            'department_id' => 3,
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
            'department_id' => 2,
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
            'department_id' => 3,
            'employment_type' => '正社員',
            'role' => 'user',
            'is_active' => true,
            'joined_at' => '2022-04-01'
        ]);
    }
}
