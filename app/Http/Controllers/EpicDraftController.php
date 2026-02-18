<?php

namespace App\Http\Controllers;

use App\Models\Epic;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\SprintStatus;
use Illuminate\Http\Request;

class EpicDraftController extends Controller
{
    public function index(Request $request)
    {
        $query = Epic::query()
            ->with(['status'])
            ->withCount('stories')
            ->whereNotNull('chat_project_id');

        if ($request->filled('project_id')) {
            $query->where('chat_project_id', $request->integer('project_id'));
        }

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$search}%");
            });
        }

        $epics = $query->orderByDesc('updated_at')->paginate(15);

        $projectMap = Project::query()
            ->active()
            ->pluck('name', 'id');

        return view('epic_drafts.index', compact('epics', 'projectMap'));
    }

    public function moveToSprint(Epic $epic)
    {
        $storyIds = $epic->stories()
            ->orderByDesc('priority')
            ->orderBy('created_at')
            ->pluck('stories.id');

        if ($storyIds->isEmpty()) {
            return back()->with('error', 'Epic has no stories to move into a sprint.');
        }

        // Promote moved epic drafts into the live planning lane by default.
        $targetStatus = SprintStatus::byKey('active')
            ?? SprintStatus::byKey('ready')
            ?? SprintStatus::byKey('draft')
            ?? SprintStatus::query()->first();

        if (! $targetStatus) {
            return back()->with('error', 'No sprint statuses available. Seed statuses first.');
        }

        $titleBase = trim($epic->title . ' Sprint Draft');
        $title = $titleBase;
        $suffix = 2;

        while (Sprint::query()->where('title', $title)->exists()) {
            $title = "{$titleBase} {$suffix}";
            $suffix++;
        }

        $sprint = Sprint::create([
            'title' => $title,
            'goal' => $epic->summary ?: "Deliver the draft scope for epic: {$epic->title}",
            'success_criteria' => "All stories selected from this epic draft are planned and execution-ready.",
            'sprint_status_id' => $targetStatus->id,
        ]);

        $syncData = [];
        foreach ($storyIds as $index => $storyId) {
            $syncData[$storyId] = ['sort_order' => $index];
        }

        $sprint->stories()->sync($syncData);

        return redirect()->route('sprints.edit', $sprint)
            ->with('success', "Epic draft moved into sprint '{$sprint->title}'.");
    }
}
