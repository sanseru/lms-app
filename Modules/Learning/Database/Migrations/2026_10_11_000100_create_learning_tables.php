<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Course = a curriculum many classes can use.
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subject');
            $table->string('level', 20)->default('beginner');
            $table->text('description')->nullable();
            $table->string('color', 20)->default('violet');
            $table->string('status', 20)->default('draft'); // draft | published
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('class_rooms', function (Blueprint $table) {
            $table->foreignId('course_id')->nullable()->after('teacher_id')->constrained()->nullOnDelete();
        });

        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->string('title');
            $table->string('type', 20)->default('material'); // material | quiz | assignment
            $table->longText('body')->nullable();             // markdown
            $table->string('video_url')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->unsignedTinyInteger('pass_score')->nullable();     // quiz
            $table->unsignedTinyInteger('max_attempts')->nullable();   // quiz, null = unlimited
            $table->unsignedSmallInteger('max_score')->nullable();     // assignment
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['course_id', 'position']);
        });

        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->text('prompt');
            $table->string('type', 20)->default('single'); // single | multiple
            $table->json('options');                       // [{text, correct}]
            $table->text('explanation')->nullable();
            $table->unsignedTinyInteger('points')->default(1);
            $table->timestamps();
        });

        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->json('answers');
            $table->unsignedTinyInteger('score');
            $table->unsignedSmallInteger('earned_points');
            $table->unsignedSmallInteger('total_points');
            $table->boolean('passed')->default(false);
            $table->timestamps();

            $table->index(['lesson_id', 'student_id']);
        });

        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->text('body')->nullable();
            $table->string('link_url')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('status', 20)->default('submitted'); // submitted | revision | graded
            $table->unsignedSmallInteger('score')->nullable();
            $table->text('feedback')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('graded_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['lesson_id', 'student_id']);
        });

        Schema::create('lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('completed_at');
            $table->timestamps();

            $table->unique(['lesson_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_progress');
        Schema::dropIfExists('assignment_submissions');
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('lessons');
        Schema::table('class_rooms', fn (Blueprint $table) => $table->dropConstrainedForeignId('course_id'));
        Schema::dropIfExists('courses');
    }
};
