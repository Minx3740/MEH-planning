
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_versions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('activity_id')
                ->constrained('activities')
                ->restrictOnDelete();

            $table->integer('version_number');

            $table->foreignId('changed_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->text('change_reason')->nullable();
            $table->json('snapshot');

            $table->timestamp('created_at')->nullable();

            $table->unique(['activity_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_versions');
    }
};
