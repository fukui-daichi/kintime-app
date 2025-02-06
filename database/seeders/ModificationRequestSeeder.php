<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ModificationRequest;
use Illuminate\Database\Seeder;

class ModificationRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 管理者を取得
        $adminUser = User::where('email', 'admin@example.com')->first();

        // 一般ユーザーを取得
        $users = User::where('user_type', 'user')->get();

        // 各ユーザーに対して10件ずつ申請を作成（合計30件）
        foreach ($users as $user) {
            // 承認待ち4件
            ModificationRequest::factory()
                ->count(4)
                ->pending()
                ->create([
                    'user_id' => $user->id,
                    'approver_id' => $adminUser->id,
                ]);

            // 承認済み4件
            ModificationRequest::factory()
                ->count(4)
                ->approved()
                ->create([
                    'user_id' => $user->id,
                    'approver_id' => $adminUser->id,
                ]);

            // 否認2件
            ModificationRequest::factory()
                ->count(2)
                ->rejected()
                ->create([
                    'user_id' => $user->id,
                    'approver_id' => $adminUser->id,
                ]);
        }
    }
}
