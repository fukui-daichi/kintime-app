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
        Schema::create('attendances', function (Blueprint $table) {
            // 主キー
            $table->id();

            // 外部キー：ユーザーテーブルとの紐付け
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // constrained()：usersテーブルへの参照制約を自動設定
            // cascadeOnDelete()：ユーザーが削除された場合、関連する勤怠記録も削除

            // 勤務日（YYYY-MM-DD形式）
            $table->date('date');

            // 出勤時刻（HH:MM:SS形式）
            // nullable()：未打刻の場合はnullを許容
            $table->time('clock_in')->nullable();

            // 退勤時刻（HH:MM:SS形式）
            $table->time('clock_out')->nullable();

            // 休憩時間（分単位）
            // default(60)：デフォルトで60分（1時間）を設定
            $table->integer('break_time')->default(60);

            // 実労働時間（分単位）
            // 実労働時間 = 退勤時刻 - 出勤時刻 - 休憩時間
            $table->integer('actual_work_time')->nullable();

            // 残業時間（分単位）
            // 所定労働時間（例：8時間）を超えた時間
            $table->integer('overtime')->nullable();

            // 深夜勤務時間（分単位）
            // 22時から翌5時までの勤務時間
            $table->integer('night_work_time')->nullable();

            // 勤務状態
            // working: 勤務中, left: 退勤済み
            $table->enum('status', ['working', 'left'])->default('working');

            // 備考欄
            $table->text('note')->nullable();

            // created_at, updated_atカラムを自動生成
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
