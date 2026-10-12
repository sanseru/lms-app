<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Tag shown on catalog cards, as in the client prototype (PROGRAMMING, DATA, TECHNOLOGY, …). */
    public function up(): void
    {
        Schema::table('programs', fn (Blueprint $table) => $table->string('category', 30)->nullable()->after('name'));
    }

    public function down(): void
    {
        Schema::table('programs', fn (Blueprint $table) => $table->dropColumn('category'));
    }
};
