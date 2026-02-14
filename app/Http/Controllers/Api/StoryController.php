<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Story;
use App\Models\StoryStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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
        $query = Story::with(['epic', 'persona', 'status']);

        // Filter by status key
        if ($request->has('status')) {
            $query->byStatus($request->input('status'));
        }

        // Filter by epic
        if ($request->has('epic_id')) {
            $query->where('epic_id', $request->input('epic_id'));
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
        ]);

        $payload = [
            'story' => [
                'id' => $story->id,
                'title' => $story->title,
                'narrative' => $story->narrative,
                'acceptance_criteria' => $story->acceptance_criteria,
                'priority' => $story->priority,
                'est_points' => $story->est_points,
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
                'success' => false,
                'error' => 'Unable to reach QAQueue service',
                'details' => $e->getMessage(),
            ], 502);
        }

        if (! $response->successful()) {
            return response()->json([
                'success' => false,
                'error' => 'QAQueue rejected task submission',
                'status' => $response->status(),
                'response' => $response->json() ?? $response->body(),
            ], 502);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tasks submitted to QAQueue',
            'story_id' => $story->id,
            'submitted_count' => count($validated['tasks']),
            'qaqueue' => $response->json(),
        ], 201);
    }

    /**
     * Format a story for API response
     */
    protected function formatStory(Story $story, bool $full = false): array
    {
        $data = [
            'id' => $story->id,
            'title' => $story->title,
            'status' => $story->status?->key,
            'status_name' => $story->status?->name,
            'priority' => $story->priority,
            'est_points' => $story->est_points,
            'epic_id' => $story->epic_id,
            'epic_title' => $story->epic?->title,
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
}
