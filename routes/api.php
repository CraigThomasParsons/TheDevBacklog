<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StoryController;
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
    
    // Get single story with epic and acceptance criteria
    // GET /api/stories/{id}
    Route::get('/{story}', [StoryController::class, 'show']);
    
    // Claim a story (mark as in_progress)
    // POST /api/stories/{id}/claim
    Route::post('/{story}/claim', [StoryController::class, 'claim']);
    
    // Mark story as completed (ready for QA)
    // POST /api/stories/{id}/complete
    Route::post('/{story}/complete', [StoryController::class, 'complete']);

    // Submit decomposed tasks to QAQueue
    // POST /api/stories/{id}/tasks
    Route::post('/{story}/tasks', [StoryController::class, 'submitTasks']);
    
    // Release a claimed story back to ready
    // POST /api/stories/{id}/release
    Route::post('/{story}/release', [StoryController::class, 'release']);
});

// Canonical Projects registry webhook for upsert/delete projection events.
Route::post('/projects/projection-sync', [ProjectProjectionSyncController::class, 'store']);

/**
 * Health check endpoint
 */
Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'service' => 'TheDevBacklog',
    'timestamp' => now()->toIso8601String(),
]));
