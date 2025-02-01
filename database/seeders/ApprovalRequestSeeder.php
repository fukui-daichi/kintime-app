<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ApprovalRequest;
use Illuminate\Database\Seeder;

class ApprovalRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // テストユーザーを取得
        $testUser = User::where('email', 'test@example.com')->first();
        $adminUser = User::where('email', 'admin@example.com')->first();

        if ($testUser && $adminUser) {
            // 承認待ちの申請を3件作成
            ApprovalRequest::factory()
                ->count(3)
                ->pending()
                ->create([
                    'user_id' => $testUser->id,
                    'approver_id' => $adminUser->id,
                ]);

            // 承認済みの申請を2件作成
            ApprovalRequest::factory()
                ->count(2)
                ->approved()
                ->create([
                    'user_id' => $testUser->id,
                    'approver_id' => $adminUser->id,
                ]);

            // 否認された申請を1件作成
            ApprovalRequest::factory()
                ->rejected()
                ->create([
                    'user_id' => $testUser->id,
                    'approver_id' => $adminUser->id,
                ]);
        }
    }
}
