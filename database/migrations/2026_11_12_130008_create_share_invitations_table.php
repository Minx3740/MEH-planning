
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('share_invitations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('share_id')
                ->constrained('activity_shares')
                ->restrictOnDelete();

            $table->foreignId('invited_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('email', 191);
            $table->string('status', 20)->default('PENDING');

            $table->string('invitation_token_hash', 255)->unique();

            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();

            $table->timestamps();

            $table->unique(['share_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('share_invitations');
    }
};
