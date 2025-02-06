<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 既存のデータをバックアップ（必要に応じて）
        $timecards = DB::table('timecards')->get();

        // 一旦 status カラムを削除
        Schema::table('timecards', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        // 新しい定義で status カラムを追加
        Schema::table('timecards', function (Blueprint $table) {
            $table->enum('status', [
                'working',           // 勤務中
                'left',             // 退勤済み
                'pending_approval', // 承認待ち
                'approved'         // 承認済み
            ])->default('working')->after('night_work_time');
        });

        // バックアップしたデータを新しい定義に合わせて復元（必要に応じて）
        foreach ($timecards as $timecard) {
            DB::table('timecards')
                ->where('id', $timecard->id)
                ->update([
                    'status' => in_array($timecard->status, ['working', 'left'])
                        ? $timecard->status
                        : 'left'
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timecards', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('timecards', function (Blueprint $table) {
            $table->enum('status', ['working', 'left'])->default('working');
        });
    }
};
