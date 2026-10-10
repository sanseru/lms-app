<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedSmallInteger('sessions_count');
            $table->unsignedBigInteger('price');
            $table->unsignedSmallInteger('valid_days')->default(90);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('student_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('package_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('class_room_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('sessions_total');
            $table->unsignedBigInteger('price');
            $table->unsignedSmallInteger('valid_days');
            $table->date('starts_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->string('status', 20)->default('pending_payment')->index();
            $table->timestamps();
            $table->index(['student_id', 'status']);
        });

        Schema::create('teacher_payouts', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedSmallInteger('sessions_count');
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('bonus')->default(0);
            $table->unsignedBigInteger('deduction')->default(0);
            $table->unsignedBigInteger('total');
            $table->string('status', 20)->default('unpaid')->index();
            $table->date('paid_at')->nullable();
            $table->string('proof_path')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('class_session_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_payout_id')->nullable()->constrained()->nullOnDelete();
            $table->date('session_date')->index();
            $table->string('description');
            $table->unsignedBigInteger('amount');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('teacher_payouts');
        Schema::dropIfExists('student_packages');
        Schema::dropIfExists('packages');
    }
};
