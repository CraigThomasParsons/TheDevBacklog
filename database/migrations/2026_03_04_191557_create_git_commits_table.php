<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('git_commits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('git_branch_id')->constrained('git_branches')->onDelete('cascade');
            $table->string('sha', 40);
            $table->text('message');
            $table->string('author', 255)->nullable();
            $table->timestamp('committed_at')->nullable();
            $table->timestamps();

            $table->unique(['git_branch_id', 'sha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('git_commits');
    }
};
