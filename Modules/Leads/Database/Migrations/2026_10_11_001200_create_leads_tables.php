<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('parent_name', 120);
            $table->string('phone', 30);
            $table->string('email', 150)->nullable();
            $table->string('child_name', 120);
            $table->unsignedTinyInteger('child_age')->nullable();
            $table->string('child_grade', 30)->nullable();
            $table->string('interest', 60)->nullable();
            $table->string('preferred_time', 150)->nullable();
            $table->string('source', 40)->nullable();
            $table->text('message')->nullable();
            $table->string('status', 20)->default('new');
            $table->string('lost_reason', 255)->nullable();

            $table->dateTime('trial_at')->nullable();
            $table->foreignId('trial_teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('trial_zoom_url')->nullable();
            $table->text('trial_result')->nullable();
            $table->unsignedTinyInteger('trial_interest')->nullable(); // 1-5, child's enthusiasm
            $table->foreignId('recommended_class_id')->nullable()->constrained('class_rooms')->nullOnDelete();

            $table->foreignId('student_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('contacted_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('trial_at');
        });

        Schema::create('lead_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20)->default('note'); // note | status
            $table->text('body');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_notes');
        Schema::dropIfExists('leads');
    }
};
