<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Scratch projects are a binary .sb3 file (sprites, sounds, blocks), not text, so they live
 * on the private disk and the project row keeps the path. Code projects keep using `code`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_projects', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('lab_projects', function (Blueprint $table) {
            $table->dropColumn('file_path');
        });
    }
};
