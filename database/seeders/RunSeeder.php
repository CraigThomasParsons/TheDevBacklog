<?php

namespace Database\Seeders;

use App\Models\Run;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RunSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $runs = [
            // Runs for completed tasks
            [
                'task_id' => 1,
                'status' => 'completed',
                'started_at' => now()->subDays(5),
                'completed_at' => now()->subDays(4),
            ],
            [
                'task_id' => 2,
                'status' => 'completed',
                'started_at' => now()->subDays(3),
                'completed_at' => now()->subDays(2),
            ],
            [
                'task_id' => 3,
                'status' => 'completed',
                'started_at' => now()->subDays(2),
                'completed_at' => now()->subDay(),
            ],
            // Run for in-progress task
            [
                'task_id' => 4,
                'status' => 'in_progress',
                'started_at' => now()->subHours(3),
                'completed_at' => null,
            ],
            // Additional run attempts
            [
                'task_id' => 1,
                'status' => 'failed',
                'started_at' => now()->subDays(6),
                'completed_at' => now()->subDays(6),
            ],
        ];

        foreach ($runs as $run) {
            Run::create($run);
        }
    }
}
