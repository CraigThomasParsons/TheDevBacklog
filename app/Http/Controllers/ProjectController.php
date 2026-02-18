<?php

namespace App\Http\Controllers;

use App\Models\Epic;
use App\Models\Project;
use App\Models\ProjectProjectionSyncEvent;
use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        try {
            $projects = Project::query()
                ->active()
                ->select(['id', 'name', 'description', 'last_synced_at'])
                ->when($search !== '', function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                })
                ->orderBy('name')
                ->get();
        } catch (\Throwable $exception) {
            return view('projects.index', [
                'projects' => collect(),
                'stats' => collect(),
                'connectionError' => 'Could not load local projects projection. Run migrations and ccdf:sync-projects.',
            ]);
        }

        $projectIds = $projects->pluck('id')->all();

        $epicCounts = Epic::query()
            ->selectRaw('chat_project_id, COUNT(*) as epic_count')
            ->whereNotNull('chat_project_id')
            ->whereIn('chat_project_id', $projectIds)
            ->groupBy('chat_project_id')
            ->pluck('epic_count', 'chat_project_id');

        $readyStoryCounts = Story::query()
            ->selectRaw('epics.chat_project_id, COUNT(stories.id) as ready_story_count')
            ->join('epics', 'stories.epic_id', '=', 'epics.id')
            ->join('story_statuses', 'stories.story_status_id', '=', 'story_statuses.id')
            ->where('story_statuses.key', 'ready')
            ->whereNotNull('epics.chat_project_id')
            ->whereIn('epics.chat_project_id', $projectIds)
            ->groupBy('epics.chat_project_id')
            ->pluck('ready_story_count', 'epics.chat_project_id');

        $sprintCounts = DB::table('sprint_stories')
            ->join('stories', 'sprint_stories.story_id', '=', 'stories.id')
            ->join('epics', 'stories.epic_id', '=', 'epics.id')
            ->selectRaw('epics.chat_project_id, COUNT(DISTINCT sprint_stories.sprint_id) as sprint_count')
            ->whereNotNull('epics.chat_project_id')
            ->whereIn('epics.chat_project_id', $projectIds)
            ->groupBy('epics.chat_project_id')
            ->pluck('sprint_count', 'epics.chat_project_id');

        $stats = collect($projectIds)->mapWithKeys(function ($projectId) use ($epicCounts, $readyStoryCounts, $sprintCounts) {
            return [
                $projectId => [
                    'epic_count' => (int) ($epicCounts[$projectId] ?? 0),
                    'ready_story_count' => (int) ($readyStoryCounts[$projectId] ?? 0),
                    'sprint_count' => (int) ($sprintCounts[$projectId] ?? 0),
                ],
            ];
        });

        $failedSyncEventsLastHour = ProjectProjectionSyncEvent::query()
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        $syncLagWarningMinutes = (int) config('services.projects_registry.sync_lag_warning_minutes', 20);

        return view('projects.index', [
            'projects' => $projects,
            'stats' => $stats,
            'failedSyncEventsLastHour' => $failedSyncEventsLastHour,
            'syncLagWarningMinutes' => $syncLagWarningMinutes,
            'connectionError' => null,
        ]);
    }
}
