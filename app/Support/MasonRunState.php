<?php

namespace App\Support;

use App\Models\MasonRunControl;
use App\Models\Sprint;
use App\Models\StoryTask;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MasonRunState
{
    /**
     * Build a snapshot of Mason's current execution state.
     *
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        $runControl = MasonRunControl::singleton();
        $heartbeatFresh = $runControl->last_heartbeat_at
            ? $runControl->last_heartbeat_at->gt(now()->subSeconds(300))
            : false;

        $currentSprint = $this->resolveCurrentSprint();

        if ($currentSprint === null) {
            return [
                'has_sprint' => false,
                'sprint' => null,
                'counts' => [],
                'wip' => [
                    'current' => 0,
                    'limit' => (int) env('MASON_WIP_LIMIT', 1),
                ],
                'current_story' => null,
                'next_ready' => [],
                'enablers' => [
                    'total' => 0,
                    'completed' => 0,
                    'remaining' => 0,
                ],
                'last_task_update_at' => null,
                'run_control' => [
                    'is_running' => (bool) $runControl->is_running,
                    'started_at' => $runControl->started_at?->toIso8601String(),
                    'stopped_at' => $runControl->stopped_at?->toIso8601String(),
                    'last_heartbeat_at' => $runControl->last_heartbeat_at?->toIso8601String(),
                    'last_status_message' => $runControl->last_status_message,
                    'heartbeat_fresh' => $heartbeatFresh,
                    'current_story_id' => $runControl->current_story_id,
                ],
            ];
        }

        $stories = $currentSprint->stories->sortBy(fn ($story) => (int) ($story->pivot->sort_order ?? 999999))->values();
        $counts = $stories
            ->groupBy(fn ($story) => $story->status?->key ?? 'unknown')
            ->map(fn (Collection $group) => $group->count())
            ->all();

        $inProgressStories = $stories->filter(fn ($story) => ($story->status?->key ?? '') === 'in_progress')->values();
        $readyStories = $stories->filter(fn ($story) => ($story->status?->key ?? '') === 'ready')->values();

        $enablers = $stories->filter(fn ($story) => ($story->story_type ?? 'feature') === 'enabler');
        $completedKeys = ['completed', 'done'];
        $completedEnablers = $enablers->filter(
            fn ($story) => in_array($story->status?->key ?? '', $completedKeys, true)
        );

        $lastTaskUpdateRaw = StoryTask::query()
            ->whereIn('story_id', $stories->pluck('id')->all())
            ->max('updated_at');

        return [
            'has_sprint' => true,
            'sprint' => [
                'id' => $currentSprint->id,
                'title' => $currentSprint->title,
                'goal' => $currentSprint->goal,
                'status' => $currentSprint->status?->key,
                'is_frozen' => (bool) $currentSprint->is_frozen,
            ],
            'counts' => $counts,
            'wip' => [
                'current' => $inProgressStories->count(),
                'limit' => (int) env('MASON_WIP_LIMIT', 1),
            ],
            'current_story' => $this->formatStory($inProgressStories->first()),
            'next_ready' => $readyStories->take(5)->map(fn ($story) => $this->formatStory($story))->values()->all(),
            'enablers' => [
                'total' => $enablers->count(),
                'completed' => $completedEnablers->count(),
                'remaining' => max($enablers->count() - $completedEnablers->count(), 0),
            ],
            'last_task_update_at' => $lastTaskUpdateRaw ? Carbon::parse($lastTaskUpdateRaw)->toIso8601String() : null,
            'run_control' => [
                'is_running' => (bool) $runControl->is_running,
                'started_at' => $runControl->started_at?->toIso8601String(),
                'stopped_at' => $runControl->stopped_at?->toIso8601String(),
                'last_heartbeat_at' => $runControl->last_heartbeat_at?->toIso8601String(),
                'last_status_message' => $runControl->last_status_message,
                'heartbeat_fresh' => $heartbeatFresh,
                'current_story_id' => $runControl->current_story_id,
            ],
        ];
    }

    private function resolveCurrentSprint(): ?Sprint
    {
        $eagerLoads = [
            'status',
            'stories' => function ($query) {
                $query->with(['status', 'persona', 'epic'])->orderByPivot('sort_order');
            },
        ];

        $activeSprint = Sprint::query()
            ->with($eagerLoads)
            ->whereHas('status', fn ($query) => $query->where('key', 'active'))
            ->latest()
            ->first();

        if ($activeSprint !== null) {
            return $activeSprint;
        }

        return Sprint::query()
            ->with($eagerLoads)
            ->whereHas('status', fn ($query) => $query->whereIn('key', ['ready', 'draft']))
            ->latest()
            ->first();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function formatStory($story): ?array
    {
        if ($story === null) {
            return null;
        }

        return [
            'id' => $story->id,
            'title' => $story->title,
            'status' => $story->status?->key,
            'priority' => (int) $story->priority,
            'story_type' => $story->story_type ?? 'feature',
            'sort_order' => (int) ($story->pivot->sort_order ?? 999999),
            'persona' => $story->persona?->name,
            'epic_title' => $story->epic?->title,
            'url' => route('stories.show', $story),
        ];
    }
}
