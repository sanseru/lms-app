<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 80);
            $table->string('description', 500)->nullable();
            $table->string('language', 20); // python | javascript | html
            $table->longText('code')->nullable();
            $table->timestamp('shared_at')->nullable();
            $table->timestamp('hidden_at')->nullable(); // removed from Community by a moderator
            $table->foreignId('hidden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['shared_at', 'hidden_at']);
        });

        Schema::create('lab_project_likes', function (Blueprint $table) {
            $table->foreignId('lab_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->primary(['lab_project_id', 'user_id']);
        });

        Schema::create('lab_project_views', function (Blueprint $table) {
            $table->foreignId('lab_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->primary(['lab_project_id', 'user_id']);
        });

        Schema::create('lab_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_comments');
        Schema::dropIfExists('lab_project_views');
        Schema::dropIfExists('lab_project_likes');
        Schema::dropIfExists('lab_projects');
    }
};
