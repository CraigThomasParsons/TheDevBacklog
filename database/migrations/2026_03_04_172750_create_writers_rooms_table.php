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
        Schema::create('writers_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->unsignedBigInteger('inception_id')->nullable(); // Reference to ChatProjects.inceptions
            $table->string('status', 30)->default('pending'); // pending, generating, completed, failed
            $table->longText('raw_llm_response')->nullable();
            $table->timestamps();
            
            $table->index('inception_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('writers_rooms');
    }
};
