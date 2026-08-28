<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('project_projection_sync_events', function (Blueprint $table) {
            $table->id();
            $table->string('source', 32); // webhook | reconciliation
            $table->string('event_type', 64)->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->string('project_uuid', 64)->nullable();
            $table->string('idempotency_key', 128)->nullable();
            $table->string('status', 32); // received | processed | duplicate | stale | failed
            $table->text('error_message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique('idempotency_key');
            $table->index(['status', 'created_at']);
            $table->index(['project_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_projection_sync_events');
    }
};
