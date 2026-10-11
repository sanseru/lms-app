<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->foreignId('replaces_session_id')->nullable()->after('class_room_id')
                ->comment('Sesi pengganti: menunjuk ke sesi asal yang dipindah atau dibatalkan')
                ->constrained('class_sessions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('replaces_session_id');
        });
    }
};
