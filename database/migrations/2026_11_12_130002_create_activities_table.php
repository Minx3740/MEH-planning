
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('direction_id')
                ->constrained('directions')
                ->restrictOnDelete();

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('location', 255)->nullable();

            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();

            $table->boolean('has_time')->default(true);

            $table->string('priority', 20)->default('NORMAL');
            $table->string('status', 20)->default('DRAFT');

            $table->timestamp('archived_at')->nullable();

            $table->foreignId('archived_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('deleted_at')->nullable();

            $table->foreignId('deleted_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
