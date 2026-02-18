<?php

namespace App\Http\Controllers;

use App\Models\Story;

class StoryController extends Controller
{
    /**
     * Display full story details.
     */
    public function show(Story $story)
    {
        // Load related context so the detail view is complete in one request.
        $story->load(['status', 'persona', 'epic', 'sprints']);

        return view('stories.show', compact('story'));
    }
}
