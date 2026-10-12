<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where students do an assignment: null = anywhere (they hand in a link, file or text),
 * 'scratch' = in the Scratch editor built into the lesson, handed in as an .sb3 file.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('workspace', 20)->nullable()->after('max_score');
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('workspace');
        });
    }
};
