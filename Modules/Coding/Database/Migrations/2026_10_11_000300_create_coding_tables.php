<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One coding exercise = one course content item (lessons.type = 'code').
        Schema::create('code_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('language', 20);             // python | javascript | html
            $table->string('mode', 20);                 // io | function | html | parsons
            $table->text('starter_code')->nullable();
            $table->text('solution_code');
            $table->string('function_name', 60)->nullable();
            $table->json('hints')->nullable();
            $table->unsignedSmallInteger('time_limit_ms')->default(2000);
            $table->unsignedTinyInteger('show_solution_after')->nullable(); // failed attempts before the solution can be seen
            $table->boolean('case_sensitive')->default(false);
            $table->string('concept', 40)->nullable();
            $table->unsignedTinyInteger('level')->nullable();
            $table->string('verified_hash', 40)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('code_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('code_exercise_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('kind', 20)->default('io'); // io | function | exists | text | attr | count | style
            $table->text('input')->nullable();         // stdin, JSON arguments, or CSS selector
            $table->text('expected')->nullable();
            $table->string('property', 60)->nullable(); // attribute name / CSS property
            $table->boolean('is_hidden')->default(false);
            $table->unsignedTinyInteger('weight')->default(1);
            $table->string('fail_message')->nullable();
            $table->timestamps();
        });

        Schema::create('code_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->longText('code');
            $table->string('status', 20);              // passed | failed | error
            $table->unsignedTinyInteger('score');
            $table->unsignedSmallInteger('passed_tests');
            $table->unsignedSmallInteger('total_tests');
            $table->json('results');
            $table->string('graded_by', 20);           // browser | judge0 | server
            $table->unsignedTinyInteger('hints_used')->default(0);
            $table->string('tests_hash', 40)->nullable();
            $table->timestamps();

            $table->index(['lesson_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('code_submissions');
        Schema::dropIfExists('code_tests');
        Schema::dropIfExists('code_exercises');
    }
};
