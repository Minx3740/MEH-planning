<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->foreignId('role_id')
                ->constrained('roles')
                ->restrictOnDelete();

            $table->foreignId('direction_id')
                ->nullable()
                ->constrained('directions')
                ->restrictOnDelete();

            $table->string('name', 100);
            $table->string('firstname', 100);
            $table->string('email', 191)->unique();
            $table->string('password');
            $table->rememberToken();
            $table->string('phone', 30)->nullable();
            $table->string('photo')->nullable();
            $table->string('status', 20)->default('ACTIVE');
            $table->timestamp('last_login')->nullable();

            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
