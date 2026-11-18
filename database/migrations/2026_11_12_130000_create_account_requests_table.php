<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('direction_id')
                ->nullable()
                ->constrained('directions')
                ->restrictOnDelete();

            $table->foreignId('requested_role_id')
                ->constrained('roles')
                ->restrictOnDelete();

            $table->string('name', 100);
            $table->string('firstname', 100);
            $table->string('email', 191);
            $table->string('password', 255);

            $table->string('phone', 30)->nullable();

            $table->string('status', 20)->default('PENDING');

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('reviewed_at')->nullable();

            $table->text('decision_reason')->nullable();

            $table->foreignId('created_user_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_requests');
    }
};
