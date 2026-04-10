<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MasonChatController;
use App\Http\Controllers\Api\StoryController;
use App\Http\Controllers\Api\MasonRunStateController;
use App\Http\Controllers\Api\InceptionWebhookController;
use App\Http\Controllers\Api\ProjectProjectionSyncController;

/**
 * Story API routes for Mason agent integration
 * 
 * These endpoints allow Mason to:
 * - Fetch stories ready for development
 * - Claim a story (mark as in_progress)
 * - Mark a story as completed
 * - Get story details with full context
 */

Route::prefix('stories')->group(function () {
    // List stories with optional status filter
    // GET /api/stories?status=ready
    Route::get('/', [StoryController::class, 'index']);

    // Trigger a sync of ready stories from WritersRoom
    // POST /api/stories/sync-ready
    Route::post('/sync-ready', [StoryController::class, 'syncReady']);
    
    // Get single story with epic and acceptance criteria
    // GET /api/stories/{id}
    Route::get('/{story}', [StoryController::class, 'show']);
    
    // Claim a story (mark as in_progress)
    // POST /api/stories/{id}/claim
    Route::post('/{story}/claim', [StoryController::class, 'claim']);
    
    // Mark story as completed (ready for QA)
    // POST /api/stories/{id}/complete
    Route::post('/{story}/complete', [StoryController::class, 'complete']);

    // Reprioritize story for sprint execution ordering
    // POST /api/stories/{id}/priority
    Route::post('/{story}/priority', [StoryController::class, 'updatePriority']);

    // Submit decomposed tasks to QAQueue
    // POST /api/stories/{id}/tasks
    Route::post('/{story}/tasks', [StoryController::class, 'submitTasks']);

    // List persisted Mason tasks for story
    // GET /api/stories/{id}/tasks
    Route::get('/{story}/tasks', [StoryController::class, 'tasks']);

    // List/add comments for story handoff and status narration
    // GET|POST /api/stories/{id}/comments
    Route::get('/{story}/comments', [StoryController::class, 'comments']);
    Route::post('/{story}/comments', [StoryController::class, 'storeComment']);

    // Update a single persisted task state by external task id
    // POST /api/stories/{id}/tasks/{externalTaskId}/state
    Route::post('/{story}/tasks/{externalTaskId}/state', [StoryController::class, 'updateTaskState']);
    
    // Release a claimed story back to ready
    // POST /api/stories/{id}/release
    Route::post('/{story}/release', [StoryController::class, 'release']);
});

// Mason runtime status endpoint for dashboard + automation introspection.
Route::get('/mason/run-state', [MasonRunStateController::class, 'show']);
Route::post('/mason/run-state/start', [MasonRunStateController::class, 'start']);
Route::post('/mason/run-state/stop', [MasonRunStateController::class, 'stop']);
Route::post('/mason/run-state/heartbeat', [MasonRunStateController::class, 'heartbeat']);
Route::post('/mason/run-state/provider', [MasonRunStateController::class, 'updateProvider']);
Route::get('/mason/chat/messages', [MasonChatController::class, 'index']);
Route::get('/mason/chat/inbox', [MasonChatController::class, 'inbox']);
Route::post('/mason/chat/messages', [MasonChatController::class, 'store']);

// Canonical Projects registry webhook for upsert/delete projection events.
Route::post('/projects/projection-sync', [ProjectProjectionSyncController::class, 'store']);

// ChatProjects pipeline webhooks.
Route::post('/inception/completed', [InceptionWebhookController::class, 'completed']);

/**
 * Health check endpoint
 */
Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'service' => 'TheDevBacklog',
    'timestamp' => now()->toIso8601String(),
]));
