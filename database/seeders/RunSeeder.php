<?php

namespace Database\Seeders;

use App\Models\Task;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RunSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $createRegForm = Task::where('title', 'Create registration form')->firstOrFail();
        $emailVerification = Task::where('title', 'Implement email verification')->firstOrFail();
        $createLoginForm = Task::where('title', 'Create login form')->firstOrFail();
        $sessionMgmt = Task::where('title', 'Implement session management')->firstOrFail();

        $createRegForm->runs()->createMany([
            [
                'status' => 'failed',
                'started_at' => now()->subDays(6),
                'completed_at' => now()->subDays(6),
            ],
            [
                'status' => 'completed',
                'started_at' => now()->subDays(5),
                'completed_at' => now()->subDays(4),
            ],
        ]);

        $emailVerification->runs()->create([
            'status' => 'completed',
            'started_at' => now()->subDays(3),
            'completed_at' => now()->subDays(2),
        ]);

        $createLoginForm->runs()->create([
            'status' => 'completed',
            'started_at' => now()->subDays(2),
            'completed_at' => now()->subDay(),
        ]);

        $sessionMgmt->runs()->create([
            'status' => 'in_progress',
            'started_at' => now()->subHours(3),
            'completed_at' => null,
        ]);
    }
}
