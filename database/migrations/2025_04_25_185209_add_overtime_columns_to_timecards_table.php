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
        Schema::table('timecards', function (Blueprint $table) {
            $table->integer('overtime_minutes')->default(0)->comment('残業時間(分)');
            $table->integer('night_minutes')->default(0)->comment('深夜時間(分)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timecards', function (Blueprint $table) {
            $table->dropColumn('overtime_minutes');
            $table->dropColumn('night_minutes');
        });
    }
};
