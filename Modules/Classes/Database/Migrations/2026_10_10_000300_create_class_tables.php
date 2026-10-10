<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('subject', 60);
            $table->string('level', 30)->default('beginner');
            $table->text('description')->nullable();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('zoom_url')->nullable();
            $table->string('zoom_meeting_id', 40)->nullable();
            $table->string('zoom_passcode', 40)->nullable();
            $table->json('schedule_days');
            $table->time('start_time');
            $table->time('end_time');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->string('color', 20)->default('violet');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('class_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('active');
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
            $table->unique(['class_room_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_students');
        Schema::dropIfExists('class_rooms');
    }
};
