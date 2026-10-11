<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('code_exercises', function (Blueprint $table) {
            $table->json('rules')->nullable()->after('case_sensitive');   // aturan kode: wajib/dilarang/maks. baris
            $table->unsignedSmallInteger('version')->default(1)->after('rules');
        });

        Schema::table('code_submissions', function (Blueprint $table) {
            $table->unsignedSmallInteger('exercise_version')->default(1)->after('tests_hash');
        });

        // Salinan isi soal yang dinilai per versi, supaya nilai lama tetap bisa dijelaskan.
        Schema::create('code_exercise_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('code_exercise_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('version');
            $table->json('snapshot');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['code_exercise_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('code_exercise_versions');
        Schema::table('code_submissions', fn (Blueprint $table) => $table->dropColumn('exercise_version'));
        Schema::table('code_exercises', fn (Blueprint $table) => $table->dropColumn(['rules', 'version']));
    }
};
