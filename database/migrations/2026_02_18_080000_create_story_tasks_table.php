<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('story_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->constrained('stories')->cascadeOnDelete();
            $table->string('external_task_id')->nullable()->index();
            $table->string('title');
            $table->longText('description')->nullable();
            $table->json('success_criteria')->nullable();
            $table->json('constraints')->nullable();
            $table->json('inputs')->nullable();
            $table->json('expected_outputs')->nullable();
            $table->string('mode', 50)->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('max_attempts')->default(3);
            $table->string('state', 50)->default('queued')->index();
            $table->string('last_provider', 100)->nullable();
            $table->string('last_run_status', 100)->nullable();
            $table->unsignedInteger('last_duration_ms')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index(['story_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('story_tasks');
    }
};
