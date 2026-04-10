<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SprintController;
use App\Http\Controllers\BacklogController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\EpicDraftController;
use App\Http\Controllers\MasonChatController;
use App\Http\Controllers\MasonRunStateController;
use App\Http\Controllers\StoryController;
use App\Http\Controllers\ThemeCustomizerController;

/**
 * Theme Customizer
 */
Route::get('/theme-customizer', [ThemeCustomizerController::class, 'index'])->name('theme.customizer');

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
Route::get('/sprint-board', [\App\Http\Controllers\SprintBoardController::class, 'index'])->name('sprints.board.index');
Route::patch('/api/sprints/{sprint}/move', [\App\Http\Controllers\SprintBoardController::class, 'update'])->name('sprints.board.move');

/**
 * Current sprint board route - visual scrum-style flow for active sprint execution.
 */
Route::get('/current-sprint', [SprintController::class, 'current'])->name('sprints.current');

/**
 * Story details route - supports opening ticket details in a new tab from the board.
 */
Route::get('/stories/{story}', [StoryController::class, 'show'])->name('stories.show');
Route::post('/stories/{story}/comments', [StoryController::class, 'storeComment'])->name('stories.comments.store');
Route::post('/stories/{story}/transition', [StoryController::class, 'transition'])->name('stories.transition');

/**
 * Mason runtime state dashboard.
 */
Route::get('/mason/state', [MasonRunStateController::class, 'index'])->name('mason.state');
Route::post('/mason/state/start', [MasonRunStateController::class, 'start'])->name('mason.state.start');
Route::post('/mason/state/stop', [MasonRunStateController::class, 'stop'])->name('mason.state.stop');
Route::post('/mason/state/provider', [MasonRunStateController::class, 'updateProvider'])->name('mason.state.provider');
Route::get('/mason/chat', [MasonChatController::class, 'index'])->name('mason.chat');

/**
 * Sprint freeze action - locks sprint from further changes
 */
Route::post('/sprints/{sprint}/freeze', [SprintController::class, 'freeze'])
    ->name('sprints.freeze');
Route::post('/sprints/{sprint}/complete', [SprintController::class, 'complete'])
    ->name('sprints.complete');

/**
 * Sprint export action - generates markdown/JSON export
 */
Route::get('/sprints/{sprint}/export', [SprintController::class, 'export'])
    ->name('sprints.export');

/**
 * File System Browser API
 */
Route::get('/api/filesystem/browse', [\App\Http\Controllers\FileSystemController::class, 'index'])
    ->name('filesystem.browse');

/**
 * Story Code Folders
 */
Route::post('/stories/{story}/code-folders', [\App\Http\Controllers\StoryCodeFolderController::class, 'store'])
    ->name('stories.code-folders.store');
Route::delete('/code-folders/{codeFolder}', [\App\Http\Controllers\StoryCodeFolderController::class, 'destroy'])
    ->name('code-folders.destroy');
