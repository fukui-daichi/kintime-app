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
        // 外部キー制約を一時的に削除
        Schema::table('requests', function (Blueprint $table) {
            $table->dropForeign(['timecard_id']);
        });

        // カラムをNULL許容に変更
        Schema::table('requests', function (Blueprint $table) {
            $table->foreignId('timecard_id')->nullable()->change();
        });

        // 外部キー制約を再設定
        Schema::table('requests', function (Blueprint $table) {
            $table->foreign('timecard_id')
                  ->references('id')
                  ->on('timecards')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 外部キー制約を一時的に削除
        Schema::table('requests', function (Blueprint $table) {
            $table->dropForeign(['timecard_id']);
        });

        // カラムを元に戻す（NOT NULL）
        Schema::table('requests', function (Blueprint $table) {
            $table->foreignId('timecard_id')->nullable(false)->change();
        });

        // 外部キー制約を再設定
        Schema::table('requests', function (Blueprint $table) {
            $table->foreign('timecard_id')
                  ->references('id')
                  ->on('timecards')
                  ->onDelete('cascade');
        });
    }
};
