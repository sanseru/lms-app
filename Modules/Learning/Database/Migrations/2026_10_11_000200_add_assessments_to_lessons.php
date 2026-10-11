<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Warm-up Mission (pre-test) and Final Mission (post-test) are flagged quizzes.
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('assessment', 10)->nullable()->after('type'); // pre | post
        });

        // Question concept (e.g. "loops") for the per-concept gain report.
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->string('concept', 40)->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_questions', fn (Blueprint $table) => $table->dropColumn('concept'));
        Schema::table('lessons', fn (Blueprint $table) => $table->dropColumn('assessment'));
    }
};
