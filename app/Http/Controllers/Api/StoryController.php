<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sprint;
use App\Models\Story;
use App\Models\StoryTask;
use App\Models\StoryStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

/**
 * Story API Controller for Mason agent integration
 * 
 * Provides REST endpoints for fetching and managing stories
 * in the CCDF automated development pipeline.
 */
class StoryController extends Controller
{
    /**
     * List stories with optional status filtering
     * 
     * GET /api/stories
     * GET /api/stories?status=ready
     * GET /api/stories?status=in_progress
     * GET /api/stories?epic_id=5
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->input('scope') === 'current_sprint') {
            return $this->indexCurrentSprintStories($request);
        }

        $query = Story::with(['epic.project', 'persona', 'status']);

        // Filter by status key
        if ($request->has('status')) {
            $query->byStatus($request->input('status'));
        }

        // Filter by epic
        if ($request->has('epic_id')) {
            $query->where('epic_id', $request->input('epic_id'));
        }

        // Filter by story type (feature|enabler)
        if ($request->has('story_type') && $request->input('story_type')) {
            $query->where('story_type', $request->input('story_type'));
        }

        // Order by priority (higher first), then by creation date
        $query->orderByDesc('priority')->orderBy('created_at');

        // Limit results if specified
        if ($request->has('limit')) {
            $query->limit((int) $request->input('limit'));
        }

        $stories = $query->get()->map(fn ($story) => $this->formatStory($story));

        return response()->json([
            'success' => true,
            'count' => $stories->count(),
            'stories' => $stories,
        ]);
    }

    /**
     * Trigger a sync of ready stories from WritersRoom.
     *
     * POST /api/stories/sync-ready
     */
    public function syncReady(Request $request): JsonResponse
    {
        $token = config('services.writersroom_sync.token');
        $provided = $request->bearerToken()
            ?? $request->header('X-DevBacklog-Token');

        // Guard clause: require token to prevent anonymous sync triggers.
        if (! $token || $provided !== $token) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
            ], 403);
        }

        // Scope sync to a project if provided, otherwise sync all ready stories.
        $projectId = $request->integer('project_id');

        $arguments = [];
        if ($projectId) {
            $arguments['--project'] = $projectId;
        } else {
            $arguments['--all'] = true;
        }

        // Reuse the existing sync command to keep behavior consistent.
        $exitCode = Artisan::call('ccdf:sync-stories', $arguments);
        $output = Artisan::output();

        // Guard clause: surface command failures with full output.
        if ($exitCode !== 0) {
            return response()->json([
                'success' => false,
                'error' => 'Sync failed',
                'details' => $output,
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sync completed',
            'output' => $output,
        ]);
    }

    /**
     * Get a single story with full details
     * 
     * GET /api/stories/{id}
     */
    public function show(Story $story): JsonResponse
    {
        $story->load(['epic', 'persona', 'status']);

        return response()->json([
            'success' => true,
            'story' => $this->formatStory($story, full: true),
        ]);
    }

    /**
     * Claim a story for development (mark as in_progress)
     * 
     * POST /api/stories/{id}/claim
     * 
     * Only stories in 'ready' status can be claimed.
     * Request body can include optional agent identifier.
     */
    public function claim(Request $request, Story $story): JsonResponse
    {
        $story->load('status');

        // Verify story is in ready status
        if ($story->status->key !== 'ready') {
            return response()->json([
                'success' => false,
                'error' => 'Story must be in ready status to claim',
                'current_status' => $story->status->key,
            ], 422);
        }

        // Find in_progress status
        $inProgressStatus = StoryStatus::byKey('in_progress');
        if (!$inProgressStatus) {
            return response()->json([
                'success' => false,
                'error' => 'in_progress status not found - run migrations',
            ], 500);
        }

        // Update story status
        $story->story_status_id = $inProgressStatus->id;
        $story->save();

        $story->load(['epic', 'persona', 'status']);

        return response()->json([
            'success' => true,
            'message' => 'Story claimed successfully',
            'story' => $this->formatStory($story, full: true),
        ]);
    }

    /**
     * Mark story as completed (ready for QA testing)
     * 
     * POST /api/stories/{id}/complete
     * 
     * Only stories in 'in_progress' status can be completed.
     */
    public function complete(Request $request, Story $story): JsonResponse
    {
        $story->load('status');

        // Verify story is in progress
        if ($story->status->key !== 'in_progress') {
            return response()->json([
                'success' => false,
                'error' => 'Story must be in_progress to complete',
                'current_status' => $story->status->key,
            ], 422);
        }

        // Find completed status
        $completedStatus = StoryStatus::byKey('completed');
        if (!$completedStatus) {
            return response()->json([
                'success' => false,
                'error' => 'completed status not found',
            ], 500);
        }

        // Update story status
        $story->story_status_id = $completedStatus->id;
        $story->save();

        $story->load(['epic', 'persona', 'status']);

        return response()->json([
            'success' => true,
            'message' => 'Story marked as completed',
            'story' => $this->formatStory($story),
        ]);
    }

    /**
     * Release a claimed story back to ready status
     * 
     * POST /api/stories/{id}/release
     * 
     * Allows Mason to release a story if it cannot be processed.
     */
    public function release(Request $request, Story $story): JsonResponse
    {
        $story->load('status');

        // Verify story is in progress
        if ($story->status->key !== 'in_progress') {
            return response()->json([
                'success' => false,
                'error' => 'Only in_progress stories can be released',
                'current_status' => $story->status->key,
            ], 422);
        }

        // Find ready status
        $readyStatus = StoryStatus::byKey('ready');
        if (!$readyStatus) {
            return response()->json([
                'success' => false,
                'error' => 'ready status not found',
            ], 500);
        }

        // Update story status
        $story->story_status_id = $readyStatus->id;
        $story->save();

        $story->load(['epic', 'persona', 'status']);

        return response()->json([
            'success' => true,
            'message' => 'Story released back to ready',
            'story' => $this->formatStory($story),
        ]);
    }

    /**
     * Update story priority to rebalance sprint execution order.
     *
     * POST /api/stories/{id}/priority
     */
    public function updatePriority(Request $request, Story $story): JsonResponse
    {
        $validated = $request->validate([
            'priority' => 'required|integer|min:0|max:100000',
        ]);

        $story->priority = (int) $validated['priority'];
        $story->save();
        $story->load(['epic', 'persona', 'status']);

        return response()->json([
            'success' => true,
            'message' => 'Story priority updated',
            'story' => $this->formatStory($story),
        ]);
    }

    /**
     * Submit decomposed tasks for a story to QAQueue.
     *
     * POST /api/stories/{id}/tasks
     */
    public function submitTasks(Request $request, Story $story): JsonResponse
    {
        $story->load(['status', 'epic']);

        if (! in_array($story->status?->key, ['ready', 'in_progress'], true)) {
            return response()->json([
                'success' => false,
                'error' => 'Story must be ready or in_progress before task submission',
                'current_status' => $story->status?->key,
            ], 422);
        }

        $validated = $request->validate([
            'agent' => 'nullable|string|max:50',
            'tasks' => 'required|array|min:1',
            'tasks.*.external_task_id' => 'nullable|string|max:100',
            'tasks.*.title' => 'required|string|max:255',
            'tasks.*.description' => 'required|string',
            'tasks.*.success_criteria' => 'required|array|min:1',
            'tasks.*.constraints' => 'nullable|array',
            'tasks.*.inputs' => 'nullable|array',
            'tasks.*.mode' => 'nullable|in:create_new,modify_existing,scaffold,analyze',
            'tasks.*.expected_outputs' => 'nullable|array',
            'tasks.*.priority' => 'nullable|integer|min:0|max:1000',
            'tasks.*.sort_order' => 'nullable|integer|min:0',
            'tasks.*.max_attempts' => 'nullable|integer|min:1|max:10',
            'tasks.*.state' => 'nullable|string|max:50',
            'tasks.*.last_provider' => 'nullable|string|max:100',
            'tasks.*.last_run_status' => 'nullable|string|max:100',
            'tasks.*.last_duration_ms' => 'nullable|integer|min:0',
        ]);

        DB::transaction(function () use ($story, $validated): void {
            $story->tasks()->delete();

            foreach ($validated['tasks'] as $index => $task) {
                StoryTask::query()->create([
                    'story_id' => $story->id,
                    'external_task_id' => $task['external_task_id'] ?? null,
                    'title' => $task['title'],
                    'description' => $task['description'],
                    'success_criteria' => $task['success_criteria'],
                    'constraints' => $task['constraints'] ?? [],
                    'inputs' => $task['inputs'] ?? [],
                    'expected_outputs' => $task['expected_outputs'] ?? [],
                    'mode' => $task['mode'] ?? null,
                    'priority' => $task['priority'] ?? 0,
                    'sort_order' => $task['sort_order'] ?? $index,
                    'max_attempts' => $task['max_attempts'] ?? 3,
                    'state' => $task['state'] ?? 'queued',
                    'last_provider' => $task['last_provider'] ?? null,
                    'last_run_status' => $task['last_run_status'] ?? null,
                    'last_duration_ms' => $task['last_duration_ms'] ?? null,
                    'last_synced_at' => now(),
                    'raw_payload' => $task,
                ]);
            }
        });

        $payload = [
            'story' => [
                'id' => $story->id,
                'title' => $story->title,
                'narrative' => $story->narrative,
                'acceptance_criteria' => $story->acceptance_criteria,
                'priority' => $story->priority,
                'est_points' => $story->est_points,
                'story_type' => $story->story_type,
                'epic' => $story->epic ? [
                    'id' => $story->epic->id,
                    'title' => $story->epic->title,
                    'summary' => $story->epic->summary,
                ] : null,
            ],
            'tasks' => $validated['tasks'],
            'source' => $validated['agent'] ?? 'mason',
        ];

        $qaQueueUrl = rtrim(config('services.qaqueue.base_url'), '/');

        try {
            $response = Http::timeout((int) config('services.qaqueue.timeout_seconds', 10))
                ->acceptJson()
                ->post("{$qaQueueUrl}/api/tasks/bulk", $payload);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => true,
                'message' => 'Tasks saved to DevBacklog; QAQueue unavailable',
                'story_id' => $story->id,
                'submitted_count' => count($validated['tasks']),
                'qaqueue_success' => false,
                'details' => $e->getMessage(),
            ], 201);
        }

        if (! $response->successful()) {
            return response()->json([
                'success' => true,
                'message' => 'Tasks saved to DevBacklog; QAQueue rejected submission',
                'story_id' => $story->id,
                'submitted_count' => count($validated['tasks']),
                'qaqueue_success' => false,
                'status' => $response->status(),
                'response' => $response->json() ?? $response->body(),
            ], 201);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tasks submitted to QAQueue',
            'story_id' => $story->id,
            'submitted_count' => count($validated['tasks']),
            'qaqueue_success' => true,
            'qaqueue' => $response->json(),
        ], 201);
    }

    /**
     * List persisted Mason tasks for a story.
     *
     * GET /api/stories/{id}/tasks
     */
    public function tasks(Story $story): JsonResponse
    {
        $story->load(['tasks', 'status']);

        return response()->json([
            'success' => true,
            'story_id' => $story->id,
            'story_status' => $story->status?->key,
            'count' => $story->tasks->count(),
            'tasks' => $story->tasks->map(fn (StoryTask $task) => [
                'id' => $task->id,
                'external_task_id' => $task->external_task_id,
                'title' => $task->title,
                'description' => $task->description,
                'success_criteria' => $task->success_criteria ?? [],
                'mode' => $task->mode,
                'priority' => $task->priority,
                'sort_order' => $task->sort_order,
                'max_attempts' => $task->max_attempts,
                'state' => $task->state,
                'last_provider' => $task->last_provider,
                'last_run_status' => $task->last_run_status,
                'last_duration_ms' => $task->last_duration_ms,
                'last_synced_at' => $task->last_synced_at?->toIso8601String(),
                'created_at' => $task->created_at?->toIso8601String(),
                'updated_at' => $task->updated_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    /**
     * Update persisted Mason task execution state for a story.
     *
     * POST /api/stories/{id}/tasks/{externalTaskId}/state
     */
    public function updateTaskState(Request $request, Story $story, string $externalTaskId): JsonResponse
    {
        $validated = $request->validate([
            'state' => 'required|string|max:50',
            'last_provider' => 'nullable|string|max:100',
            'last_run_status' => 'nullable|string|max:100',
            'last_duration_ms' => 'nullable|integer|min:0',
        ]);

        $task = StoryTask::query()
            ->where('story_id', $story->id)
            ->where('external_task_id', $externalTaskId)
            ->first();

        if ($task === null) {
            return response()->json([
                'success' => false,
                'error' => 'Task not found for story',
            ], 404);
        }

        $task->state = $validated['state'];
        $task->last_provider = $validated['last_provider'] ?? $task->last_provider;
        $task->last_run_status = $validated['last_run_status'] ?? $task->last_run_status;
        $task->last_duration_ms = $validated['last_duration_ms'] ?? $task->last_duration_ms;
        $task->save();

        return response()->json([
            'success' => true,
            'task' => [
                'id' => $task->id,
                'external_task_id' => $task->external_task_id,
                'state' => $task->state,
                'last_provider' => $task->last_provider,
                'last_run_status' => $task->last_run_status,
                'last_duration_ms' => $task->last_duration_ms,
                'updated_at' => $task->updated_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Format a story for API response
     */
    protected function formatStory(Story $story, bool $full = false): array
    {
        $project = $story->epic?->project;

        $data = [
            'id' => $story->id,
            'title' => $story->title,
            'status' => $story->status?->key,
            'status_name' => $story->status?->name,
            'priority' => $story->priority,
            'est_points' => $story->est_points,
            'story_type' => $story->story_type,
            'is_enabler' => $story->story_type === 'enabler',
            'epic_id' => $story->epic_id,
            'epic_title' => $story->epic?->title,
            'project' => $project ? [
                'id' => $project->id,
                'name' => $project->name,
                'code_folder' => $project->code_folder,
                'local_location' => $project->local_location,
                'github_repo' => $project->github_repo,
                'gitea_location' => $project->gitea_location,
                'framework_description' => $project->framework_description,
                'languages' => $project->languages,
            ] : null,
            'persona' => $story->persona?->name,
            'created_at' => $story->created_at?->toIso8601String(),
            'updated_at' => $story->updated_at?->toIso8601String(),
        ];

        // Include full content for detail views
        if ($full) {
            $data['narrative'] = $story->narrative;
            $data['acceptance_criteria'] = $story->acceptance_criteria;
            $data['epic_summary'] = $story->epic?->summary;
        }

        return $data;
    }

    /**
     * List stories in the current sprint board order.
     *
     * GET /api/stories?scope=current_sprint[&project_id=4]
     */
    protected function indexCurrentSprintStories(Request $request): JsonResponse
    {
        $currentSprint = Sprint::query()
            ->with([
                'status',
                'stories' => function ($query) {
                    $query->with(['status', 'persona', 'epic.project'])->orderByPivot('sort_order');
                },
            ])
            ->whereHas('status', fn ($query) => $query->where('key', 'active'))
            ->latest()
            ->first();

        if ($currentSprint === null) {
            $currentSprint = Sprint::query()
                ->with([
                    'status',
                    'stories' => function ($query) {
                        $query->with(['status', 'persona', 'epic.project'])->orderByPivot('sort_order');
                    },
                ])
                ->whereHas('status', fn ($query) => $query->whereIn('key', ['ready', 'draft']))
                ->latest()
                ->first();
        }

        if ($currentSprint === null) {
            return response()->json([
                'success' => true,
                'count' => 0,
                'scope' => 'current_sprint',
                'sprint' => null,
                'stories' => [],
            ]);
        }

        $stories = $currentSprint->stories;
        $projectId = $request->integer('project_id');
        if ($projectId > 0) {
            $stories = $stories->filter(fn ($story) => (int) ($story->epic?->chat_project_id ?? 0) === $projectId)->values();
        }

        return response()->json([
            'success' => true,
            'count' => $stories->count(),
            'scope' => 'current_sprint',
            'sprint' => [
                'id' => $currentSprint->id,
                'title' => $currentSprint->title,
                'goal' => $currentSprint->goal,
                'status' => $currentSprint->status?->key,
                'is_frozen' => (bool) $currentSprint->is_frozen,
            ],
            'stories' => $stories->map(fn ($story) => $this->formatStory($story, full: true))->values(),
        ]);
    }
}
