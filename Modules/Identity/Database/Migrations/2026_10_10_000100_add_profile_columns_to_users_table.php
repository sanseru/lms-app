<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('phone', 30)->nullable()->after('email');
            $table->string('status', 30)->default('active')->index()->after('password');
            $table->string('avatar_path')->nullable()->after('status');
            $table->timestamp('last_login_at')->nullable()->after('avatar_path');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'phone', 'status', 'avatar_path', 'last_login_at', 'deleted_at']);
        });
    }
};
