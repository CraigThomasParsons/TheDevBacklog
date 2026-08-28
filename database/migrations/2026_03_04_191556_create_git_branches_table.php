<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('git_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('git_repo_id')->constrained('git_repos')->onDelete('cascade');
            $table->string('name', 255);
            $table->foreignId('story_id')->nullable()->constrained('stories')->nullOnDelete();
            $table->foreignId('feature_id')->nullable()->constrained('features')->nullOnDelete();
            $table->foreignId('work_run_id')->nullable()->constrained('work_runs')->nullOnDelete();
            $table->boolean('is_merged')->default(false);
            $table->timestamp('merged_at')->nullable();
            $table->string('last_commit_sha', 40)->nullable();
            $table->timestamps();

            $table->unique(['git_repo_id', 'name']);
            $table->index('story_id');
            $table->index('feature_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('git_branches');
    }
};
