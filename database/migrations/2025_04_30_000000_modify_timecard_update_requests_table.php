<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('timecard_update_requests', function (Blueprint $table) {
            // 既存の不要なカラムを削除
            $table->dropColumn(['original_time', 'corrected_time', 'correction_type']);

            // 元の時間を保存するカラムを追加
            $table->dateTime('original_clock_in')->nullable();
            $table->dateTime('original_clock_out')->nullable();
            $table->dateTime('original_break_start')->nullable();
            $table->dateTime('original_break_end')->nullable();

            // 修正後の時間を保存するカラムを追加
            $table->dateTime('corrected_clock_in')->nullable();
            $table->dateTime('corrected_clock_out')->nullable();
            $table->dateTime('corrected_break_start')->nullable();
            $table->dateTime('corrected_break_end')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timecard_update_requests', function (Blueprint $table) {
            // 追加したカラムを削除
            $table->dropColumn([
                'original_clock_in',
                'original_clock_out',
                'original_break_start',
                'original_break_end',
                'corrected_clock_in',
                'corrected_clock_out',
                'corrected_break_start',
                'corrected_break_end'
            ]);

            // 元のカラムを再作成
            $table->dateTime('original_time');
            $table->dateTime('corrected_time');
            $table->enum('correction_type', ['clock_in', 'clock_out', 'break_start', 'break_end']);
        });
    }
};
