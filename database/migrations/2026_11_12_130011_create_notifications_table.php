<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('actor_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('activity_id')
                ->nullable()
                ->constrained('activities')
                ->restrictOnDelete();

            $table->foreignId('invitation_id')
                ->nullable()
                ->constrained('share_invitations')
                ->restrictOnDelete();

            $table->string('type', 50);
            $table->string('title', 200);
            $table->text('message')->nullable();

            $table->timestamp('read_at')->nullable();
            $table->timestamp('deleted_at')->nullable();

            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
