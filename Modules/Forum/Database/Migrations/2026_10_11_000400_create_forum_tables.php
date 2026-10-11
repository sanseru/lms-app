<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 150);
            $table->text('body');
            $table->text('code')->nullable();
            $table->string('image_path')->nullable();
            $table->string('visibility', 10)->default('class'); // class | private (hanya guru)
            $table->boolean('anonymous')->default(false);
            $table->boolean('pinned')->default(false);
            $table->unsignedBigInteger('accepted_answer_id')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('staff_answered_at')->nullable();
            $table->unsignedInteger('answers_count')->default(0);
            $table->unsignedInteger('votes_count')->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->index(['class_room_id', 'last_activity_at']);
        });

        Schema::create('forum_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('forum_questions')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->text('code')->nullable();
            $table->boolean('by_staff')->default(false);
            $table->unsignedInteger('votes_count')->default(0);
            $table->timestamps();
        });

        Schema::create('forum_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('votable');
            $table->timestamps();

            $table->unique(['user_id', 'votable_type', 'votable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_votes');
        Schema::dropIfExists('forum_answers');
        Schema::dropIfExists('forum_questions');
    }
};
