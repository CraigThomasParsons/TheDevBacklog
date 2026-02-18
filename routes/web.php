<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SprintController;
use App\Http\Controllers\BacklogController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\EpicDraftController;
use App\Http\Controllers\MasonRunStateController;
use App\Http\Controllers\StoryController;

/**
 * Home route - redirects to sprints dashboard
 */
Route::get('/', function () {
    return redirect()->route('sprints.index');
});

/**
 * Backlog routes - shows ready stories for sprint planning
 */
Route::get('/backlog', [BacklogController::class, 'index'])->name('backlog.index');

/**
 * Project overview route - surfaces project context in DevBacklog
 */
Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');

/**
 * Epic drafts route - treat epics as sprint draft candidates
 */
Route::get('/epic-drafts', [EpicDraftController::class, 'index'])->name('epic-drafts.index');
Route::post('/epic-drafts/{epic}/move-to-sprint', [EpicDraftController::class, 'moveToSprint'])
    ->name('epic-drafts.move-to-sprint');

/**
 * Sprint resource routes for CRUD operations
 */
Route::resource('sprints', SprintController::class);
Route::post('/sprints/{sprint}/board', [SprintController::class, 'updateBoard'])
    ->name('sprints.board.update');

/**
 * Current sprint board route - visual scrum-style flow for active sprint execution.
 */
Route::get('/current-sprint', [SprintController::class, 'current'])->name('sprints.current');

/**
 * Story details route - supports opening ticket details in a new tab from the board.
 */
Route::get('/stories/{story}', [StoryController::class, 'show'])->name('stories.show');

/**
 * Mason runtime state dashboard.
 */
Route::get('/mason/state', [MasonRunStateController::class, 'index'])->name('mason.state');
Route::post('/mason/state/start', [MasonRunStateController::class, 'start'])->name('mason.state.start');
Route::post('/mason/state/stop', [MasonRunStateController::class, 'stop'])->name('mason.state.stop');

/**
 * Sprint freeze action - locks sprint from further changes
 */
Route::post('/sprints/{sprint}/freeze', [SprintController::class, 'freeze'])
    ->name('sprints.freeze');

/**
 * Sprint export action - generates markdown/JSON export
 */
Route::get('/sprints/{sprint}/export', [SprintController::class, 'export'])
    ->name('sprints.export');
