<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->unsignedInteger('rate_per_session')->default(0)->after('expertise')->comment('Honor per sesi (Rp)');
            $table->string('bank_name', 60)->nullable()->after('rate_per_session');
            $table->string('bank_account', 40)->nullable()->after('bank_name');
        });
    }

    public function down(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->dropColumn(['rate_per_session', 'bank_name', 'bank_account']);
        });
    }
};
