<?php

namespace Database\Seeders;

use App\Models\Epic;
use App\Models\Story;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stories = [
            // User Management System Stories
            [
                'epic_id' => 1,
                'title' => 'User Registration',
                'description' => 'Implement user registration with email verification',
                'status' => 'completed',
            ],
            [
                'epic_id' => 1,
                'title' => 'User Login',
                'description' => 'Implement secure user login with session management',
                'status' => 'in_progress',
            ],
            [
                'epic_id' => 1,
                'title' => 'Password Reset',
                'description' => 'Allow users to reset their password via email',
                'status' => 'pending',
            ],
            // Reporting Dashboard Stories
            [
                'epic_id' => 2,
                'title' => 'Sales Analytics',
                'description' => 'Display sales data with charts and graphs',
                'status' => 'pending',
            ],
            [
                'epic_id' => 2,
                'title' => 'User Activity Reports',
                'description' => 'Show user engagement and activity metrics',
                'status' => 'pending',
            ],
        ];

        foreach ($stories as $story) {
            Story::create($story);
        }
    }
}
