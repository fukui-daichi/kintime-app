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
        Schema::create('timecards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->time('clock_in')->nullable();
            $table->time('clock_out')->nullable();
            // 時間を分単位の整数で保存
            $table->integer('break_time')->nullable()->comment('休憩時間（分）');
            $table->integer('actual_work_time')->nullable()->comment('実労働時間（分）');
            $table->integer('overtime')->nullable()->comment('残業時間（分）');
            $table->integer('night_work_time')->nullable()->comment('深夜時間（分）');
            $table->enum('status', ['working', 'left', 'pending_approval', 'approved'])->default('working');
            $table->text('note')->nullable();
            $table->timestamps();

            // インデックスの追加
            $table->index(['user_id', 'date']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timecards');
    }
};
