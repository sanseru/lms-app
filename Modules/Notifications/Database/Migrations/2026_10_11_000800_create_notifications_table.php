<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->string('dedupe_key')->nullable()->comment('Mencegah notifikasi ganda untuk kejadian yang sama');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['notifiable_type', 'notifiable_id', 'read_at']);
            $table->unique(['notifiable_type', 'notifiable_id', 'dedupe_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
