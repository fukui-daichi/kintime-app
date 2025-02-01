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
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('approver_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('attendance_id')->constrained()->onDelete('cascade');
            $table->enum('request_type', ['time_correction', 'break_time_modification', 'vacation']);
            $table->dateTime('before_clock_in')->nullable();
            $table->dateTime('before_clock_out')->nullable();
            $table->decimal('before_break_hours', 3, 2)->nullable();
            $table->dateTime('after_clock_in')->nullable();
            $table->dateTime('after_clock_out')->nullable();
            $table->decimal('after_break_hours', 3, 2)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('reason');
            $table->text('comment')->nullable();
            $table->timestamps();

            // インデックスの追加
            $table->index(['user_id', 'status']);
            $table->index(['approver_id', 'status']);
            $table->index('attendance_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};
