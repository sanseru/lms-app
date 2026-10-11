<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->string('emoji', 16)->default('🚀');
            $table->date('due_date')->nullable();
            $table->boolean('requires_review')->default(true);
            $table->string('status', 20)->default('active'); // active | archived
            $table->timestamps();
        });

        Schema::create('project_template_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('project_boards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('result_url')->nullable();
            $table->text('result_note')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'student_id']);
        });

        Schema::create('project_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained('project_boards')->cascadeOnDelete();
            $table->foreignId('template_task_id')->nullable()->constrained('project_template_tasks')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->string('column', 10)->default('todo'); // todo | doing | review | done
            $table->unsignedInteger('position')->default(0);
            $table->text('feedback')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->timestamps();

            $table->index(['board_id', 'column', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_cards');
        Schema::dropIfExists('project_boards');
        Schema::dropIfExists('project_template_tasks');
        Schema::dropIfExists('projects');
    }
};
