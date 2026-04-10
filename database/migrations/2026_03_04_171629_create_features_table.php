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
        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('sprint_id')->nullable()->constrained('sprints')->onDelete('set null');
            $table->foreignId('story_id')->nullable()->constrained('stories')->onDelete('set null');
            
            $table->string('title', 200);
            $table->longText('description')->nullable();
            $table->string('status', 30)->default('ready'); // ready|building|qa|done|blocked
            $table->integer('priority')->default(0);
            
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['project_id', 'status']);
            $table->index('sprint_id');
            $table->index('story_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('features');
    }
};
