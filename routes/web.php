<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SprintController;
use App\Http\Controllers\BacklogController;

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
 * Sprint resource routes for CRUD operations
 */
Route::resource('sprints', SprintController::class);

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
