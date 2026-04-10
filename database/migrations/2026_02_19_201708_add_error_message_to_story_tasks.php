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
            $table->text('error_message')->nullable()->after('last_provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('story_tasks', function (Blueprint $table) {
            $table->dropColumn('error_message');
        });
    }
};
