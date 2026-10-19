<?php

namespace Tests\Feature;

use App\Models\Epic;
use App\Models\EpicStatus;
use App\Models\Run;
use App\Models\Story;
use App\Models\StoryStatus;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BacklogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_backlog_endpoint_returns_successful_response(): void
    {
        $response = $this->getJson('/api/backlog');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ])
            ->assertJson(['success' => true]);
    }

    public function test_backlog_endpoint_returns_nested_hierarchy(): void
    {
        $epicStatus = EpicStatus::create(['key' => 'active', 'name' => 'Active']);
        $storyStatus = StoryStatus::create(['key' => 'ready', 'name' => 'Ready']);

        $epic = Epic::create([
            'title' => 'Test Epic',
            'summary' => 'An epic for testing',
            'epic_status_id' => $epicStatus->id,
        ]);

        $story = $epic->stories()->create([
            'title' => 'Test Story',
            'narrative' => 'As a user I want to test',
            'story_status_id' => $storyStatus->id,
        ]);

        $task = $story->backlogTasks()->create([
            'title' => 'Test Task',
            'description' => 'A task for testing',
            'status' => 'pending',
        ]);

        $task->runs()->create([
            'status' => 'completed',
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);

        $response = $this->getJson('/api/backlog');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.0.title', 'Test Epic')
            ->assertJsonPath('data.0.stories.0.title', 'Test Story')
            ->assertJsonPath('data.0.stories.0.backlog_tasks.0.title', 'Test Task')
            ->assertJsonPath('data.0.stories.0.backlog_tasks.0.runs.0.status', 'completed');
    }

    public function test_backlog_endpoint_returns_empty_data_when_no_epics(): void
    {
        $response = $this->getJson('/api/backlog');

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'data' => []]);
    }
}
