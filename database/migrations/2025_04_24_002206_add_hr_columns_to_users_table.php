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
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_number')->unique()->after('id');
            $table->string('last_name')->after('employee_number');
            $table->string('first_name')->after('last_name');
            $table->unsignedBigInteger('department_id')->nullable()->after('first_name');
            $table->string('employment_type')->after('department_id');
            $table->enum('role', ['admin', 'manager', 'user'])->after('employment_type');
            $table->boolean('is_active')->default(true)->after('role');
            $table->date('joined_at')->after('is_active');
            $table->date('leaved_at')->nullable()->after('joined_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['employee_number']);
            $table->dropColumn([
                'employee_number',
                'last_name',
                'first_name',
                'department_id',
                'employment_type',
                'role',
                'is_active',
                'joined_at',
                'leaved_at'
            ]);
        });
    }
};
