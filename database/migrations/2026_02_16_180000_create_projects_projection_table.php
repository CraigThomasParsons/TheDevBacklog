<?php

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
        Schema::create('projects', function (Blueprint $table) {
            // Preserve source numeric ID compatibility with ChatProjects.
            $table->unsignedBigInteger('id')->primary();

            // Long-lived cross-service identity for future decoupling/moves.
            $table->uuid('project_uuid')->nullable()->unique();

            // Canonical project metadata projected from Projects API.
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('code_folder', 500)->nullable();
            $table->string('local_location', 500)->nullable();
            $table->string('github_repo', 500)->nullable();
            $table->string('gitea_location', 500)->nullable();
            $table->text('framework_description')->nullable();
            $table->string('languages')->nullable();

            // Source and sync bookkeeping for idempotent projection updates.
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('deleted_at')->nullable();

            $table->timestamps();

            $table->index('name');
            $table->index('source_updated_at');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
