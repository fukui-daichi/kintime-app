<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Request;
use Illuminate\Database\Seeder;

class RequestSeeder extends Seeder
{
    public function run(): void
    {
        // 管理者を取得
        $adminUser = User::where('email', 'admin@example.com')->first();

        // 一般ユーザーを取得
        $users = User::where('user_type', 'user')->get();

        // 各ユーザーに対して申請を作成
        foreach ($users as $user) {
            // 勤怠修正申請
            // 承認待ち4件
            Request::factory()
                ->count(4)
                ->state([
                    'request_type' => 'timecard',
                    'status' => 'pending',
                    'user_id' => $user->id,
                    'approver_id' => $adminUser->id,
                ])
                ->create();

            // 承認済み4件
            Request::factory()
                ->count(4)
                ->state([
                    'request_type' => 'timecard',
                    'status' => 'approved',
                    'user_id' => $user->id,
                    'approver_id' => $adminUser->id,
                ])
                ->create();

            // 否認2件
            Request::factory()
                ->count(2)
                ->state([
                    'request_type' => 'timecard',
                    'status' => 'rejected',
                    'user_id' => $user->id,
                    'approver_id' => $adminUser->id,
                ])
                ->create();

            // 有給休暇申請
            Request::factory()
                ->count(3)
                ->state([
                    'request_type' => 'paid_vacation',
                    'user_id' => $user->id,
                    'approver_id' => $adminUser->id,
                ])
                ->create();
        }
    }
}
