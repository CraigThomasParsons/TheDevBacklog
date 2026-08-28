<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lookup tables first
        Schema::create('epic_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('story_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('sprint_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->timestamps();
        });

        // Core tables
        Schema::create('personas', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('summary')->nullable();
            $table->text('details')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('epics', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->foreignId('epic_status_id')->constrained('epic_statuses');
            $table->timestamps();
        });

        Schema::create('stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('epic_id')->nullable()->constrained('epics')->nullOnDelete();
            $table->foreignId('persona_id')->nullable()->constrained('personas')->nullOnDelete();

            $table->string('title');
            $table->text('narrative');
            $table->longText('acceptance_criteria')->nullable();

            $table->foreignId('story_status_id')->constrained('story_statuses');

            $table->integer('priority')->default(0);
            $table->integer('est_points')->nullable();
            $table->timestamps();

            $table->index(['story_status_id', 'priority']);
        });

        Schema::create('sprints', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('goal');
            $table->longText('success_criteria')->nullable();

            $table->foreignId('sprint_status_id')->constrained('sprint_statuses');

            $table->boolean('is_frozen')->default(false);
            $table->timestamp('frozen_at')->nullable();

            $table->timestamps();
        });

        Schema::create('sprint_stories', function (Blueprint $table) {
            $table->foreignId('sprint_id')->constrained('sprints')->cascadeOnDelete();
            $table->foreignId('story_id')->constrained('stories')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);

            $table->primary(['sprint_id', 'story_id']);
            $table->index(['sprint_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sprint_stories');
        Schema::dropIfExists('sprints');
        Schema::dropIfExists('stories');
        Schema::dropIfExists('epics');
        Schema::dropIfExists('personas');
        Schema::dropIfExists('sprint_statuses');
        Schema::dropIfExists('story_statuses');
        Schema::dropIfExists('epic_statuses');
    }
};
