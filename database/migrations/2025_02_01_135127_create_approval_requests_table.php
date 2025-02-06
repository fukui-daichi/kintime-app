<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('approver_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('timecard_id')->constrained()->onDelete('cascade');
            $table->enum('request_type', ['time_correction', 'break_time_modification']);
            $table->time('before_clock_in')->nullable();
            $table->time('before_clock_out')->nullable();
            $table->integer('before_break_time')->nullable()->comment('修正前の休憩時間（分）');
            $table->time('after_clock_in')->nullable();
            $table->time('after_clock_out')->nullable();
            $table->integer('after_break_time')->nullable()->comment('修正後の休憩時間（分）');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('reason');
            $table->text('comment')->nullable();
            $table->timestamps();

            // インデックスの追加
            $table->index(['user_id', 'status']);
            $table->index(['approver_id', 'status']);
            $table->index('timecard_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};
