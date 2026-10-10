<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('zoom_url')->nullable();
            $table->string('status', 20)->default('scheduled')->index();
            $table->string('topic')->nullable();
            $table->text('homework')->nullable();
            $table->text('parent_note')->nullable()->comment('Catatan untuk orang tua');
            $table->text('internal_note')->nullable()->comment('Hanya admin dan guru');
            $table->boolean('flagged')->default(false)->index();
            $table->string('recording_url')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['class_room_id', 'starts_at']);
            $table->index(['teacher_id', 'starts_at']);
        });

        Schema::create('session_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20);
            $table->string('note')->nullable();
            $table->boolean('counts_quota')->default(false);
            $table->unsignedBigInteger('student_package_id')->nullable()->index();
            $table->timestamps();
            $table->unique(['class_session_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_attendances');
        Schema::dropIfExists('class_sessions');
    }
};
