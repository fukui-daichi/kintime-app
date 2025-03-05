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
        Schema::table('timecards', function (Blueprint $table) {
            // 有給休暇種別を追加
            $table->enum('vacation_type', ['full', 'am', 'pm'])->nullable()->after('night_work_time')
                ->comment('有給休暇種別（全日/午前半休/午後半休）');

            // statusカラムの定義を変更して'paid_vacation'を追加
            // ENUMを変更するためにはいったん削除して再作成する必要がある

            // 現在のステータスをバックアップ
            $table->string('status_backup')->nullable()->after('status');
        });

        // 既存のstatusの値をバックアップに保存
        DB::statement('UPDATE timecards SET status_backup = status');

        // statusカラムを削除
        Schema::table('timecards', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        // 新しい値を含むstatusカラムを再作成
        Schema::table('timecards', function (Blueprint $table) {
            $table->enum('status', [
                'working', 'left', 'pending_approval', 'approved', 'paid_vacation'
            ])->default('working')->after('night_work_time')
              ->comment('勤務状態（出勤中/退勤済み/承認待ち/承認済み/有給休暇）');
        });

        // バックアップからstatusを復元
        DB::statement('UPDATE timecards SET status = status_backup');

        // バックアップカラムを削除
        Schema::table('timecards', function (Blueprint $table) {
            $table->dropColumn('status_backup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 同様の手順で元に戻す
        Schema::table('timecards', function (Blueprint $table) {
            $table->string('status_backup')->nullable();
        });

        DB::statement('UPDATE timecards SET status_backup = status');

        Schema::table('timecards', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('timecards', function (Blueprint $table) {
            $table->enum('status', [
                'working', 'left', 'pending_approval', 'approved'
            ])->default('working');
        });

        DB::statement('UPDATE timecards SET status = status_backup WHERE status_backup IN (\'working\', \'left\', \'pending_approval\', \'approved\')');

        Schema::table('timecards', function (Blueprint $table) {
            $table->dropColumn('status_backup');
            $table->dropColumn('vacation_type');
        });
    }
};
