<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mason_run_controls', function (Blueprint $table): void {
            $table->id();
            $table->boolean('is_running')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('stopped_at')->nullable();
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->unsignedBigInteger('current_story_id')->nullable();
            $table->string('last_status_message', 255)->nullable();
            $table->json('heartbeat_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mason_run_controls');
    }
};
