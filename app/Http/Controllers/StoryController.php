<?php

namespace App\Http\Controllers;

use App\Models\Story;
use App\Models\StoryComment;
use App\Models\StoryStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StoryController extends Controller
{
    /**
     * Display full story details.
     */
    public function show(Story $story)
    {
        // Load related context so the detail view is complete in one request.
        $story->load(['status', 'persona', 'epic.project', 'sprints', 'tasks', 'comments']);

        $latestBlocker = $story->comments
            ->where('source', 'mason')
            ->where('kind', 'blocker')
            ->sortByDesc('created_at')
            ->first();

        $hasHumanReplyAfterBlocker = false;
        if ($latestBlocker) {
            $hasHumanReplyAfterBlocker = $story->comments
                ->where('source', 'human')
                ->first(fn ($comment) => $comment->created_at && $comment->created_at->gt($latestBlocker->created_at)) !== null;
        }

        $waitingForHumanReply = $latestBlocker !== null && ! $hasHumanReplyAfterBlocker;

        return view('stories.show', compact('story', 'waitingForHumanReply', 'latestBlocker'));
    }

    public function storeComment(Request $request, Story $story): RedirectResponse
    {
        $validated = $request->validate([
            'author_name' => 'nullable|string|max:120',
            'body' => 'required|string|max:5000',
        ]);

        StoryComment::query()->create([
            'story_id' => $story->id,
            'author_name' => trim($validated['author_name'] ?? '') ?: 'Anonymous',
            'source' => 'human',
            'kind' => 'note',
            'body' => $validated['body'],
        ]);

        return redirect()
            ->route('stories.show', $story)
            ->with('success', 'Comment added.');
    }

    public function transition(Request $request, Story $story): RedirectResponse
    {
        $transitions = [
            'draft'       => 'ready',
            'ready'       => 'in_progress',
            'in_progress' => 'in_testing',
            'in_testing'  => 'completed',
        ];

        $currentKey = $story->status?->key;
        $nextKey = $transitions[$currentKey] ?? null;

        if ($nextKey) {
            $nextStatus = StoryStatus::where('key', $nextKey)->firstOrFail();
            $story->story_status_id = $nextStatus->id;
            $story->save();
        }

        return redirect()->route('stories.show', $story);
    }
}
