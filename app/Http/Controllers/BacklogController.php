<?php

namespace App\Http\Controllers;

use App\Models\Story;
use Illuminate\Http\Request;

/**
 * BacklogController displays all ready stories not yet assigned to a sprint.
 *
 * This provides a view of work items available for sprint planning.
 */
class BacklogController extends Controller
{
    /**
     * Display the backlog of ready stories.
     *
     * Shows stories that are marked as ready but not yet
     * assigned to any sprint.
     */
    public function index(Request $request)
    {
        // Build query for ready stories
        $storiesQuery = Story::ready()
            ->with(['status', 'epic', 'persona']);

        // Filter by epic if provided
        if ($request->has('epic_id') && $request->epic_id) {
            $storiesQuery->where('epic_id', $request->epic_id);
        }

        // Order by priority descending to show most important first
        $stories = $storiesQuery
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('backlog.index', compact('stories'));
    }
}
