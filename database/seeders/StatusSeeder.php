<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatusSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Epic statuses
        DB::table('epic_statuses')->insert([
            ['key' => 'backlog', 'name' => 'Backlog', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'active', 'name' => 'Active', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'done', 'name' => 'Done', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'archived', 'name' => 'Archived', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // Story statuses - includes QA workflow statuses
        DB::table('story_statuses')->insert([
            ['key' => 'draft', 'name' => 'Draft', 'created_at' => $now, 'updated_at' => $now],           // id=1
            ['key' => 'ready', 'name' => 'Ready', 'created_at' => $now, 'updated_at' => $now],           // id=2
            ['key' => 'completed', 'name' => 'Completed', 'created_at' => $now, 'updated_at' => $now],   // id=3
            ['key' => 'in_testing', 'name' => 'In Testing', 'created_at' => $now, 'updated_at' => $now], // id=4
            ['key' => 'passed', 'name' => 'Passed', 'created_at' => $now, 'updated_at' => $now],         // id=5
            ['key' => 'failed', 'name' => 'Failed', 'created_at' => $now, 'updated_at' => $now],         // id=6
            ['key' => 'archived', 'name' => 'Archived', 'created_at' => $now, 'updated_at' => $now],     // id=7
        ]);

        // Sprint statuses
        DB::table('sprint_statuses')->insert([
            ['key' => 'draft', 'name' => 'Draft', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'ready', 'name' => 'Ready', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'active', 'name' => 'Active', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'closed', 'name' => 'Closed', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'archived', 'name' => 'Archived', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
