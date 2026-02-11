<?php

namespace Database\Seeders;

use App\Models\Task;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tasks = [
            // User Registration Tasks
            [
                'story_id' => 1,
                'title' => 'Create registration form',
                'description' => 'Design and implement registration form with validation',
                'status' => 'completed',
            ],
            [
                'story_id' => 1,
                'title' => 'Implement email verification',
                'description' => 'Send verification email and validate token',
                'status' => 'completed',
            ],
            // User Login Tasks
            [
                'story_id' => 2,
                'title' => 'Create login form',
                'description' => 'Design and implement login form',
                'status' => 'completed',
            ],
            [
                'story_id' => 2,
                'title' => 'Implement session management',
                'description' => 'Handle user sessions and remember me functionality',
                'status' => 'in_progress',
            ],
            [
                'story_id' => 2,
                'title' => 'Add rate limiting',
                'description' => 'Prevent brute force attacks with rate limiting',
                'status' => 'pending',
            ],
            // Password Reset Tasks
            [
                'story_id' => 3,
                'title' => 'Create password reset form',
                'description' => 'Design password reset request form',
                'status' => 'pending',
            ],
            // Sales Analytics Tasks
            [
                'story_id' => 4,
                'title' => 'Design dashboard layout',
                'description' => 'Create wireframes and implement base dashboard layout',
                'status' => 'pending',
            ],
        ];

        foreach ($tasks as $task) {
            Task::create($task);
        }
    }
}
