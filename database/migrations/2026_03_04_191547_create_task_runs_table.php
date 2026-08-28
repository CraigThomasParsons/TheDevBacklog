<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_run_id')->constrained('work_runs')->onDelete('cascade');
            $table->foreignId('story_task_id')->nullable()->constrained('story_tasks')->nullOnDelete();
            $table->foreignId('feature_id')->nullable()->constrained('features')->nullOnDelete();
            $table->string('assignee_agent', 60)->nullable();
            $table->string('status', 30)->default('pending');
            $table->longText('output')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['work_run_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_runs');
    }
};
