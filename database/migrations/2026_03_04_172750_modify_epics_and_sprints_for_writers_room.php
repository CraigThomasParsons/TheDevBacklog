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
        if (!Schema::hasColumn('epics', 'inception_id')) {
            Schema::table('epics', function (Blueprint $table) {
                // chat_project_id exists. Track origin.
                $table->unsignedBigInteger('inception_id')->nullable()->after('chat_project_id');
            });
        }

        Schema::table('sprints', function (Blueprint $table) {
            $table->string('created_from', 50)->default('manual')->after('sprint_status_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('epics', function (Blueprint $table) {
            $table->dropColumn('inception_id');
        });
        
        Schema::table('sprints', function (Blueprint $table) {
            $table->dropColumn('created_from');
        });
    }
};
