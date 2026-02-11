<?php

use App\Http\Controllers\Api\BacklogController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/backlog', [BacklogController::class, 'index']);
