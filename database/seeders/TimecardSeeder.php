<?php

namespace Database\Seeders;

use App\Models\Timecard;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TimecardSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('role', '!=', 'admin')->get();
        $yesterday = now()->subDay();
        $startDate = $yesterday->copy()->subDays(180);

        foreach ($users as $user) {
            $currentDate = $startDate->copy();
            $workDays = collect();

            // 勤務日リスト作成 (週末除外)
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
