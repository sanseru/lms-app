<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_report_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('class_room_id')->constrained()->cascadeOnDelete();
            $table->date('month'); // first day of the month
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('highlights')->nullable(); // going well
            $table->text('focus')->nullable();      // focus for next month
            $table->timestamps();

            $table->unique(['student_id', 'class_room_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_report_notes');
    }
};
