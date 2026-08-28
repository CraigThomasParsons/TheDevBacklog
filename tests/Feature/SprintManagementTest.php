<?php

namespace Tests\Feature;

use App\Models\Sprint;
use App\Models\SprintStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SprintManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\StatusSeeder::class);
    }

    public function test_can_view_sprint_board()
    {
        $response = $this->get(route('sprints.board.index'));
        $response->assertStatus(200);
        $response->assertViewIs('sprints.board');
    }

    public function test_making_sprint_active_demotes_existing_active_sprint()
    {
        $activeStatus = SprintStatus::where('key', 'active')->first();
        $readyStatus = SprintStatus::where('key', 'ready')->first();

        // 1. Create an existing active sprint
        $currentActive = Sprint::factory()->create([
            'title' => 'Current Active',
            'goal' => 'Goal 1',
            'sprint_status_id' => $activeStatus->id,
        ]);

        // 2. Create a backlog sprint
        $newSprint = Sprint::factory()->create([
            'title' => 'Next Up',
            'goal' => 'Goal 2',
            'sprint_status_id' => $readyStatus->id,
        ]);

        // 3. Move new sprint to active via API
        $response = $this->patchJson(route('sprints.board.move', $newSprint), [
            'column' => 'active',
        ]);

        $response->assertStatus(200);

        // 4. Verify outcomes
        $currentActive->refresh();
        $newSprint->refresh();

        $this->assertEquals('ready', $currentActive->status->key);
        $this->assertEquals('active', $newSprint->status->key);
    }

    public function test_moving_sprint_to_history_closes_it()
    {
        $activeStatus = SprintStatus::where('key', 'active')->first();
        
        $sprint = Sprint::factory()->create([
            'title' => 'To Close',
            'goal' => 'Goal',
            'sprint_status_id' => $activeStatus->id,
        ]);

        $response = $this->patchJson(route('sprints.board.move', $sprint), [
            'column' => 'history',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('closed', $sprint->refresh()->status->key);
    }

    public function test_moving_sprint_to_backlog_makes_it_ready()
    {
         $closedStatus = SprintStatus::where('key', 'closed')->first();
        
        $sprint = Sprint::factory()->create([
            'title' => 'Reopen',
            'goal' => 'Goal',
            'sprint_status_id' => $closedStatus->id,
        ]);

        $response = $this->patchJson(route('sprints.board.move', $sprint), [
            'column' => 'backlog',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('ready', $sprint->refresh()->status->key);
    }
}
