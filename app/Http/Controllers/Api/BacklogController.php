<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Epic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BacklogController extends Controller
{
    /**
     * Display the backlog hierarchy.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $epics = Epic::with([
            'stories.backlogTasks.runs'
        ])->get();

        return response()->json([
            'success' => true,
            'data' => $epics,
        ]);
    }
}
