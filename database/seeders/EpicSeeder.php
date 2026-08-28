<?php

namespace Database\Seeders;

use App\Models\Epic;
use App\Models\EpicStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EpicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $activeStatus = EpicStatus::where('key', 'active')->firstOrFail();
        $backlogStatus = EpicStatus::where('key', 'backlog')->firstOrFail();

        $epics = [
            [
                'title' => 'User Management System',
                'summary' => 'Build complete user management functionality including authentication, authorization, and profile management',
                'epic_status_id' => $activeStatus->id,
            ],
            [
                'title' => 'Reporting Dashboard',
                'summary' => 'Create comprehensive reporting dashboard with analytics and data visualization',
                'epic_status_id' => $backlogStatus->id,
            ],
            [
                'title' => 'API Integration',
                'summary' => 'Integrate with third-party APIs for extended functionality',
                'epic_status_id' => $backlogStatus->id,
            ],
        ];

        foreach ($epics as $epic) {
            Epic::create($epic);
        }
    }
}
