<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('student_code', 30)->unique();
            $table->date('birth_date')->nullable();
            $table->string('gender', 10)->nullable();
            $table->string('school')->nullable();
            $table->string('grade', 30)->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->date('joined_at')->nullable();
            $table->timestamps();
        });

        Schema::create('teacher_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('employee_code', 30)->unique();
            $table->string('expertise')->nullable();
            $table->text('bio')->nullable();
            $table->date('joined_at')->nullable();
            $table->timestamps();
        });

        Schema::create('guardian_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guardian_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('relationship', 30)->default('orang_tua');
            $table->timestamps();
            $table->unique(['guardian_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardian_student');
        Schema::dropIfExists('teacher_profiles');
        Schema::dropIfExists('student_profiles');
    }
};
