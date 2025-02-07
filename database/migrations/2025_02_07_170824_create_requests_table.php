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
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('approver_id')->constrained('users')->onDelete('cascade');
            $table->enum('request_type', ['timecard', 'paid_vacation']);
            $table->date('target_date');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('reason');
            $table->text('comment')->nullable();
            // 打刻修正用
            $table->time('before_clock_in')->nullable();
            $table->time('before_clock_out')->nullable();
            $table->time('after_clock_in')->nullable();
            $table->time('after_clock_out')->nullable();
            $table->integer('before_break_time')->nullable();
            $table->integer('after_break_time')->nullable();
            // 有給休暇申請用
            $table->enum('vacation_type', ['full', 'am', 'pm'])->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            // インデックス
            $table->index(['user_id', 'status']);
            $table->index(['approver_id', 'status']);
            $table->index('target_date');
            $table->index('approved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
