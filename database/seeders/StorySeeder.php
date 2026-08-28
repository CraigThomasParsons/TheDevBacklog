<?php

namespace Database\Seeders;

use App\Models\Epic;
use App\Models\Story;
use App\Models\StoryStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $readyStatus = StoryStatus::where('key', 'ready')->firstOrFail();
        $draftStatus = StoryStatus::where('key', 'draft')->firstOrFail();
        $completedStatus = StoryStatus::where('key', 'completed')->firstOrFail();

        $userManagement = Epic::where('title', 'User Management System')->firstOrFail();
        $reporting = Epic::where('title', 'Reporting Dashboard')->firstOrFail();

        $userManagement->stories()->createMany([
            [
                'title' => 'User Registration',
                'narrative' => 'As a new user, I want to register so that I can access the system',
                'story_status_id' => $completedStatus->id,
            ],
            [
                'title' => 'User Login',
                'narrative' => 'As a registered user, I want to log in with session management',
                'story_status_id' => $readyStatus->id,
            ],
            [
                'title' => 'Password Reset',
                'narrative' => 'As a user, I want to reset my password via email',
                'story_status_id' => $draftStatus->id,
            ],
        ]);

        $reporting->stories()->createMany([
            [
                'title' => 'Sales Analytics',
                'narrative' => 'As a manager, I want to see sales data with charts and graphs',
                'story_status_id' => $draftStatus->id,
            ],
            [
                'title' => 'User Activity Reports',
                'narrative' => 'As a manager, I want to see user engagement and activity metrics',
                'story_status_id' => $draftStatus->id,
            ],
        ]);
    }
}
