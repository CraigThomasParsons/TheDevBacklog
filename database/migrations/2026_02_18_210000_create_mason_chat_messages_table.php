<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mason_chat_messages', function (Blueprint $table): void {
            $table->id();
            $table->string('sender', 20);
            $table->string('status', 20)->default('pending');
            $table->text('body');
            $table->foreignId('in_reply_to_id')->nullable()->constrained('mason_chat_messages')->nullOnDelete();
            $table->foreignId('related_story_id')->nullable()->constrained('stories')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mason_chat_messages');
    }
};
