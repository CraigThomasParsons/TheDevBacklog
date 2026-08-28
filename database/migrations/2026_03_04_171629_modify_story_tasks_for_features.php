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
        Schema::table('story_tasks', function (Blueprint $table) {
            $table->foreignId('feature_id')->nullable()->after('story_id')->constrained('features')->onDelete('cascade');
            $table->foreignId('story_id')->nullable()->change();
            $table->string('assignee_agent', 60)->nullable()->after('state');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('story_tasks', function (Blueprint $table) {
            $table->dropForeign(['feature_id']);
            $table->dropColumn('feature_id');
            $table->dropColumn('assignee_agent');
            // Revert story_id to required (if it was)
            $table->foreignId('story_id')->nullable(false)->change();
        });
    }
};
