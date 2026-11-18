
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_share_activities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('share_id')
                ->constrained('activity_shares')
                ->restrictOnDelete();

            $table->foreignId('activity_id')
                ->constrained('activities')
                ->restrictOnDelete();

            $table->timestamp('created_at')->nullable();

            $table->unique(['share_id', 'activity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_share_activities');
    }
};
