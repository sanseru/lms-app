<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->unsignedSmallInteger('time_limit_minutes')->nullable()->after('max_attempts'); // kuis, null = tanpa batas waktu
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->unsignedInteger('duration_seconds')->nullable()->after('passed');
            $table->boolean('late')->default(false)->after('duration_seconds');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropColumn(['duration_seconds', 'late']);
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('time_limit_minutes');
        });
    }
};
