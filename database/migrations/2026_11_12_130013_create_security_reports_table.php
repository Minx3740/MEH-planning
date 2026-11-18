<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('reported_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('subject_user_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('audit_log_id')
                ->nullable()
                ->constrained('audit_logs')
                ->restrictOnDelete();

            $table->string('category', 100);
            $table->text('description');
            $table->string('status', 20)->default('OPEN');

            $table->foreignId('resolved_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_reports');
    }
};
