<?php

namespace Database\Seeders;

use App\Models\Epic;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EpicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $epics = [
            [
                'title' => 'User Management System',
                'description' => 'Build complete user management functionality including authentication, authorization, and profile management',
                'status' => 'in_progress',
            ],
            [
                'title' => 'Reporting Dashboard',
                'description' => 'Create comprehensive reporting dashboard with analytics and data visualization',
                'status' => 'pending',
            ],
            [
                'title' => 'API Integration',
                'description' => 'Integrate with third-party APIs for extended functionality',
                'status' => 'pending',
            ],
        ];

        foreach ($epics as $epic) {
            Epic::create($epic);
        }
    }
}
