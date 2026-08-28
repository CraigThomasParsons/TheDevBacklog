<?php

namespace Database\Seeders;

use App\Models\Story;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $registration = Story::where('title', 'User Registration')->firstOrFail();
        $login = Story::where('title', 'User Login')->firstOrFail();
        $passwordReset = Story::where('title', 'Password Reset')->firstOrFail();
        $salesAnalytics = Story::where('title', 'Sales Analytics')->firstOrFail();

        $registration->tasks()->createMany([
            [
                'title' => 'Create registration form',
                'description' => 'Design and implement registration form with validation',
                'status' => 'completed',
            ],
            [
                'title' => 'Implement email verification',
                'description' => 'Send verification email and validate token',
                'status' => 'completed',
            ],
        ]);

        $login->tasks()->createMany([
            [
                'title' => 'Create login form',
                'description' => 'Design and implement login form',
                'status' => 'completed',
            ],
            [
                'title' => 'Implement session management',
                'description' => 'Handle user sessions and remember me functionality',
                'status' => 'in_progress',
            ],
            [
                'title' => 'Add rate limiting',
                'description' => 'Prevent brute force attacks with rate limiting',
                'status' => 'pending',
            ],
        ]);

        $passwordReset->tasks()->createMany([
            [
                'title' => 'Create password reset form',
                'description' => 'Design password reset request form',
                'status' => 'pending',
            ],
        ]);

        $salesAnalytics->tasks()->createMany([
            [
                'title' => 'Design dashboard layout',
                'description' => 'Create wireframes and implement base dashboard layout',
                'status' => 'pending',
            ],
        ]);
    }
}
