<?php

namespace App\Http\Controllers;

use App\Models\MasonRunControl;
use App\Models\Sprint;
use App\Models\SprintStatus;
use App\Models\Story;
use App\Models\StoryStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SprintController extends Controller
{
    /**
     * Display the current sprint in a 4-column scrum board layout.
     */
    public function current()
    {
        // Select the primary current sprint by precedence: active -> ready -> latest draft.
        $currentSprint = Sprint::query()
            ->with(['status', 'stories' => function ($query) {
                $query->with(['status', 'persona', 'epic'])->orderByPivot('sort_order');
            }])
            ->whereHas('status', fn ($query) => $query->where('key', 'active'))
            ->latest()
            ->first();

        if ($currentSprint === null) {
            $currentSprint = Sprint::query()
                ->with(['status', 'stories' => function ($query) {
                    $query->with(['status', 'persona', 'epic'])->orderByPivot('sort_order');
                }])
                ->whereHas('status', fn ($query) => $query->whereIn('key', ['ready', 'draft']))
                ->latest()
                ->first();
        }

        // Return early with empty columns when no sprint is available yet.
        if ($currentSprint === null) {
            $masonRunControl = MasonRunControl::singleton();
            $providerOptions = config('mason.provider_options', []);
            $masonHeartbeatFresh = $masonRunControl->last_heartbeat_at
                ? $masonRunControl->last_heartbeat_at->gt(now()->subSeconds(300))
                : false;

            return view('sprints.current', [
                'currentSprint' => null,
                'toDoStories' => collect(),
                'inProgressStories' => collect(),
                'inReviewStories' => collect(),
                'doneStories' => collect(),
                'masonRunControl' => $masonRunControl,
                'masonHeartbeatFresh' => $masonHeartbeatFresh,
                'providerOptions' => $providerOptions,
            ]);
        }

        $toDoStories = collect();
        $inProgressStories = collect();
        $inReviewStories = collect();
        $doneStories = collect();

        // Group stories by status keys while remaining tolerant to status vocabulary differences.
        foreach ($currentSprint->stories as $story) {
            $statusKey = $story->status?->key;

            if (in_array($statusKey, ['in_progress', 'doing'], true)) {
                $inProgressStories->push($story);
                continue;
            }

            if (in_array($statusKey, ['in_review', 'review', 'qa', 'testing'], true)) {
                $inReviewStories->push($story);
                continue;
            }

            if (in_array($statusKey, ['done', 'completed', 'passed'], true)) {
                $doneStories->push($story);
                continue;
            }

            // Default unknown or empty statuses into To Do so no story disappears from the board.
            $toDoStories->push($story);
        }

        $masonRunControl = MasonRunControl::singleton();
        $providerOptions = config('mason.provider_options', []);
        $masonHeartbeatFresh = $masonRunControl->last_heartbeat_at
            ? $masonRunControl->last_heartbeat_at->gt(now()->subSeconds(300))
            : false;

        return view('sprints.current', compact(
            'currentSprint',
            'toDoStories',
            'inProgressStories',
            'inReviewStories',
            'doneStories',
            'masonRunControl',
            'masonHeartbeatFresh',
            'providerOptions'
        ));
    }

    public function index(Request $request)
    {
        $query = Sprint::with(['status', 'stories']);

        if ($request->has('status') && $request->status) {
            $query->whereHas('status', fn ($q) => $q->where('key', $request->status));
        }

        $sprints = $query->orderBy('created_at', 'desc')->paginate(10);
        $statuses = SprintStatus::all();

        return view('sprints.index', compact('sprints', 'statuses'));
    }

    public function create()
    {
        $statuses = SprintStatus::all();
        $availableStories = Story::ready()->with(['persona', 'epic'])->get();

        return view('sprints.create', compact('statuses', 'availableStories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'goal' => 'required|string',
            'success_criteria' => 'nullable|string',
            'sprint_status_id' => 'required|exists:sprint_statuses,id',
            'stories' => 'array',
            'stories.*' => 'exists:stories,id',
        ]);

        $sprint = Sprint::create([
            'title' => $validated['title'],
            'goal' => $validated['goal'],
            'success_criteria' => $validated['success_criteria'] ?? null,
            'sprint_status_id' => $validated['sprint_status_id'],
        ]);

        // Attach stories with sort order
        if (!empty($validated['stories'])) {
            $storyData = [];
            foreach ($validated['stories'] as $index => $storyId) {
                $storyData[$storyId] = ['sort_order' => $index];
            }
            $sprint->stories()->attach($storyData);
        }

        return redirect()->route('sprints.show', $sprint)
            ->with('success', 'Sprint created successfully.');
    }

    public function show(Sprint $sprint)
    {
        $sprint->load(['status', 'stories' => function ($query) {
            $query->with(['status', 'persona', 'epic'])->orderByPivot('sort_order');
        }]);

        return view('sprints.show', compact('sprint'));
    }

    public function edit(Sprint $sprint)
    {
        if ($sprint->is_frozen) {
            return redirect()->route('sprints.show', $sprint)
                ->with('error', 'Cannot edit a frozen sprint.');
        }

        $sprint->load('stories');
        $statuses = SprintStatus::all();
        $availableStories = Story::ready()
            ->whereNotIn('id', $sprint->stories->pluck('id'))
            ->with(['persona', 'epic'])
            ->get();

        return view('sprints.edit', compact('sprint', 'statuses', 'availableStories'));
    }

    public function update(Request $request, Sprint $sprint)
    {
        if ($sprint->is_frozen) {
            return redirect()->route('sprints.show', $sprint)
                ->with('error', 'Cannot edit a frozen sprint.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'goal' => 'required|string',
            'success_criteria' => 'nullable|string',
            'sprint_status_id' => 'required|exists:sprint_statuses,id',
            'stories' => 'array',
            'stories.*' => 'exists:stories,id',
        ]);

        $sprint->update([
            'title' => $validated['title'],
            'goal' => $validated['goal'],
            'success_criteria' => $validated['success_criteria'] ?? null,
            'sprint_status_id' => $validated['sprint_status_id'],
        ]);

        // Sync stories with sort order
        $storyData = [];
        if (!empty($validated['stories'])) {
            foreach ($validated['stories'] as $index => $storyId) {
                $storyData[$storyId] = ['sort_order' => $index];
            }
        }
        $sprint->stories()->sync($storyData);

        return redirect()->route('sprints.show', $sprint)
            ->with('success', 'Sprint updated successfully.');
    }

    public function destroy(Sprint $sprint)
    {
        if ($sprint->is_frozen) {
            return redirect()->route('sprints.show', $sprint)
                ->with('error', 'Cannot delete a frozen sprint.');
        }

        $sprint->delete();

        return redirect()->route('sprints.index')
            ->with('success', 'Sprint deleted successfully.');
    }

    public function freeze(Sprint $sprint)
    {
        if ($sprint->stories->count() === 0) {
            return back()->with('error', 'Cannot freeze a sprint with no stories.');
        }

        $sprint->freeze();

        return redirect()->route('sprints.show', $sprint)
            ->with('success', 'Sprint frozen. Context is now immutable.');
    }

    public function complete(Sprint $sprint)
    {
        $sprint->load(['status', 'stories.status']);

        if (in_array($sprint->status?->key, ['closed', 'archived'], true)) {
            return redirect()->route('sprints.show', $sprint)
                ->with('success', 'Sprint is already completed.');
        }

        $closedStatus = SprintStatus::byKey('closed') ?? SprintStatus::byKey('archived');
        if (! $closedStatus) {
            return redirect()->route('sprints.show', $sprint)
                ->with('error', 'No closed/archived sprint status found.');
        }

        $readyStoryStatus = StoryStatus::byKey('ready') ?? StoryStatus::byKey('draft');
        $doneKeys = ['done', 'completed', 'passed'];

        $movedStoryCount = 0;
        $nextSprintTitle = null;

        DB::transaction(function () use ($sprint, $closedStatus, $readyStoryStatus, $doneKeys, &$movedStoryCount, &$nextSprintTitle): void {
            $incompleteStories = $sprint->stories->filter(
                fn ($story) => ! in_array($story->status?->key, $doneKeys, true)
            )->values();

            if ($incompleteStories->isNotEmpty()) {
                $nextSprint = Sprint::query()
                    ->with('status')
                    ->where('id', '!=', $sprint->id)
                    ->whereHas('status', fn ($query) => $query->whereIn('key', ['draft', 'ready']))
                    ->orderBy('created_at')
                    ->first();

                if ($nextSprint === null) {
                    $nextSprint = $this->createCarryoverSprint($sprint);
                }

                $nextSprintTitle = $nextSprint->title;
                $maxSortOrder = DB::table('sprint_stories')->where('sprint_id', $nextSprint->id)->max('sort_order');
                $nextSortOrder = $maxSortOrder !== null ? ((int) $maxSortOrder + 1) : 0;

                foreach ($incompleteStories as $story) {
                    $nextSprint->stories()->syncWithoutDetaching([
                        $story->id => ['sort_order' => $nextSortOrder],
                    ]);
                    $nextSortOrder++;

                    if ($readyStoryStatus && ! in_array($story->status?->key, $doneKeys, true)) {
                        $story->story_status_id = $readyStoryStatus->id;
                        $story->save();
                    }

                    $sprint->stories()->detach($story->id);
                    $movedStoryCount++;
                }
            }

            $sprint->sprint_status_id = $closedStatus->id;
            $sprint->save();
        });

        $message = "Sprint completed. Moved {$movedStoryCount} unfinished stor" . ($movedStoryCount === 1 ? 'y' : 'ies');
        if ($movedStoryCount > 0 && $nextSprintTitle) {
            $message .= " to '{$nextSprintTitle}'.";
        } else {
            $message .= '.';
        }

        return redirect()->route('sprints.show', $sprint)
            ->with('success', $message);
    }

    public function exportSpec(Sprint $sprint)
    {
        $markdown = $sprint->toSpecMarkdown();
        $filename = "SprintSpec-{$sprint->id}-" . now()->format('Y-m-d') . ".md";

        return response($markdown)
            ->header('Content-Type', 'text/markdown')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Persist current sprint board changes (column/status + order).
     */
    public function updateBoard(Request $request, Sprint $sprint): JsonResponse
    {
        $validated = $request->validate([
            'columns' => ['required', 'array'],
            'columns.todo' => ['required', 'array'],
            'columns.in_progress' => ['required', 'array'],
            'columns.in_review' => ['required', 'array'],
            'columns.done' => ['required', 'array'],
            'columns.todo.*' => ['integer'],
            'columns.in_progress.*' => ['integer'],
            'columns.in_review.*' => ['integer'],
            'columns.done.*' => ['integer'],
        ]);

        $columnMap = [
            'todo' => ['ready', 'draft'],
            'in_progress' => ['in_progress', 'doing'],
            'in_review' => ['in_review', 'review', 'qa', 'in_testing', 'testing'],
            'done' => ['done', 'completed', 'passed'],
        ];

        $statusByColumn = [];
        foreach ($columnMap as $columnKey => $candidateKeys) {
            $status = $this->resolveStatusByPriority($candidateKeys);
            if (! $status) {
                return response()->json([
                    'success' => false,
                    'error' => "No story status found for board column '{$columnKey}'.",
                ], 422);
            }
            $statusByColumn[$columnKey] = $status;
        }

        $sprintStoryIds = $sprint->stories()->pluck('stories.id')->map(fn ($id) => (int) $id)->values();
        $incomingStoryIds = collect($validated['columns'])
            ->flatten()
            ->map(fn ($id) => (int) $id)
            ->values();

        // Guard clause: prevent losing stories due to partial payloads.
        if ($incomingStoryIds->count() !== $sprintStoryIds->count()) {
            return response()->json([
                'success' => false,
                'error' => 'Board payload does not include the full sprint story set.',
            ], 422);
        }

        if ($incomingStoryIds->duplicates()->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'error' => 'Board payload contains duplicate story ids.',
            ], 422);
        }

        if ($incomingStoryIds->diff($sprintStoryIds)->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'error' => 'Board payload contains stories outside this sprint.',
            ], 422);
        }

        DB::transaction(function () use ($validated, $sprint, $statusByColumn): void {
            $sortOrder = 0;

            foreach (['todo', 'in_progress', 'in_review', 'done'] as $columnKey) {
                $status = $statusByColumn[$columnKey];
                $storyIds = collect($validated['columns'][$columnKey])->map(fn ($id) => (int) $id);

                foreach ($storyIds as $storyId) {
                    Story::query()
                        ->where('id', $storyId)
                        ->update(['story_status_id' => $status->id]);

                    $sprint->stories()->updateExistingPivot($storyId, ['sort_order' => $sortOrder]);
                    $sortOrder++;
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Board updated.',
        ]);
    }

    private function resolveStatusByPriority(array $keys): ?StoryStatus
    {
        foreach ($keys as $key) {
            $status = StoryStatus::byKey($key);
            if ($status) {
                return $status;
            }
        }

        return null;
    }

    private function createCarryoverSprint(Sprint $sourceSprint): Sprint
    {
        $draftStatus = SprintStatus::byKey('draft') ?? SprintStatus::query()->first();
        $titleBase = trim($sourceSprint->title . ' - Carryover');
        $title = $titleBase;
        $suffix = 2;

        while (Sprint::query()->where('title', $title)->exists()) {
            $title = "{$titleBase} {$suffix}";
            $suffix++;
        }

        return Sprint::create([
            'title' => $title,
            'goal' => "Carry over unfinished work from '{$sourceSprint->title}'.",
            'success_criteria' => 'Complete all unfinished stories carried from previous sprint.',
            'sprint_status_id' => $draftStatus?->id,
            'is_frozen' => false,
        ]);
    }
}
