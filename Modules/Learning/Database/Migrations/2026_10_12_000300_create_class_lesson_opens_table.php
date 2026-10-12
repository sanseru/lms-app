<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lessons a teacher opened early for one class (the client's "Open / Locked" switch).
     * node_key names a circle on the map: "u{unit_id}" for a numbered lesson,
     * "l{lesson_id}" for an item that is not in a lesson.
     */
    public function up(): void
    {
        Schema::create('class_lesson_opens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('node_key', 24);
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['class_room_id', 'node_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_lesson_opens');
    }
};
