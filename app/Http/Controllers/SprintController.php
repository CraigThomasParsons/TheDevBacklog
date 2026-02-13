<?php

namespace App\Http\Controllers;

use App\Models\Sprint;
use App\Models\SprintStatus;
use App\Models\Story;
use Illuminate\Http\Request;

class SprintController extends Controller
{
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

    public function exportSpec(Sprint $sprint)
    {
        $markdown = $sprint->toSpecMarkdown();
        $filename = "SprintSpec-{$sprint->id}-" . now()->format('Y-m-d') . ".md";

        return response($markdown)
            ->header('Content-Type', 'text/markdown')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
