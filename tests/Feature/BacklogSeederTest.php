<?php

namespace Tests\Feature;

use App\Models\Epic;
use App\Models\Run;
use App\Models\Task;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BacklogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_builds_the_complete_backlog_hierarchy(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('epics', 3);
        $this->assertDatabaseCount('stories', 5);
        $this->assertDatabaseCount('tasks', 7);
        $this->assertDatabaseCount('runs', 5);

        $task = Task::query()
            ->where('title', 'Create registration form')
            ->firstOrFail();

        $this->assertSame('User Registration', $task->story->title);
        $this->assertCount(2, $task->runs);
        $this->assertSame(3, Epic::query()->count());
        $this->assertSame(5, Run::query()->count());
    }
}
