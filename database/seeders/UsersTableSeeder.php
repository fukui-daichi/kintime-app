<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 管理者ユーザーのテストアカウント
        User::create([
            'first_name' => '管理者',
            'last_name' => '総務',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'user_type' => 'admin',
            'hire_date' => '2024-01-01',
        ]);

        // 一般ユーザー3名を作成
        for ($i = 1; $i <= 3; $i++) {
            User::create([
                'first_name' => 'テスト',
                'last_name' => "ユーザー{$i}",
                'email' => "test{$i}@example.com",
                'password' => Hash::make('password'),
                'user_type' => 'user',
                'hire_date' => '2024-01-01',
            ]);
        }
    }
}
