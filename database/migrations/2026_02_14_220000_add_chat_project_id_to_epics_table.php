<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('epics', function (Blueprint $table) {
            if (! Schema::hasColumn('epics', 'chat_project_id')) {
                $table->unsignedBigInteger('chat_project_id')->nullable()->after('id');
                $table->index('chat_project_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('epics', function (Blueprint $table) {
            if (Schema::hasColumn('epics', 'chat_project_id')) {
                $table->dropIndex(['chat_project_id']);
                $table->dropColumn('chat_project_id');
            }
        });
    }
};
