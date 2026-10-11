<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Misi Pemanasan (pre-test) dan Misi Akhir (post-test) adalah kuis yang ditandai.
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('assessment', 10)->nullable()->after('type'); // pre | post
        });

        // Konsep soal (mis. "perulangan") untuk laporan kenaikan per konsep.
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
