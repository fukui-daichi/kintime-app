<?php

namespace Database\Seeders;

use App\Models\Timecard;
use App\Models\TimecardUpdateRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class TimecardUpdateRequestSeeder extends Seeder
{
    public function run()
    {
        $users = User::where('role', 'user')->get();

        foreach ($users as $user) {
            $timecards = Timecard::where('user_id', $user->id)
                ->where('date', '>=', now()->subDays(30))
                ->inRandomOrder()
                ->limit(5)
                ->get();

            foreach ($timecards as $timecard) {
                TimecardUpdateRequest::factory()->create([
                    'user_id' => $user->id,
                    'timecard_id' => $timecard->id,
                ]);
            }
        }
    }
}
