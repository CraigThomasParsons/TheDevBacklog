<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add in_progress status for stories claimed by Mason
        DB::table('story_statuses')->insert([
            'key' => 'in_progress',
            'name' => 'In Progress',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('story_statuses')->where('key', 'in_progress')->delete();
    }
};
