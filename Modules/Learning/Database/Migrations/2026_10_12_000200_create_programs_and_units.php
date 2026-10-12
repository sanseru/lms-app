<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Program → level → lesson → task, as in the client's prototype:
 * - programs: a programming language or track (Python, Scratch, Web)
 * - courses: become the levels of a program (Python Start 1, Python Start 2, …), ordered by level_order
 * - course_units: the numbered "lessons" on the map; existing lessons become the tasks inside them
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('description', 500)->nullable();
            $table->string('color', 20)->default('violet');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->foreignId('program_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('level_order')->nullable()->after('program_id');
        });

        Schema::create('course_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title', 120);
            $table->timestamps();
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('course_id')->constrained('course_units')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lessons', fn (Blueprint $table) => $table->dropConstrainedForeignId('unit_id'));
        Schema::dropIfExists('course_units');
        Schema::table('courses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('program_id');
            $table->dropColumn('level_order');
        });
        Schema::dropIfExists('programs');
    }
};
