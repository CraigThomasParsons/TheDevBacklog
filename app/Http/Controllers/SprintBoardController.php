<?php

namespace App\Http\Controllers;

use App\Models\Sprint;
use App\Models\SprintStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SprintBoardController extends Controller
{
    /**
     * Display the Sprint Board (Backlog, Active, History).
     */
    public function index(Request $request)
    {
        $showClosed = $request->boolean('show_closed', false);

        $activeSprint = Sprint::whereHas('status', fn($q) => $q->where('key', 'active'))->first();

        $backlogSprints = Sprint::whereHas('status', fn($q) => $q->whereIn('key', ['draft', 'ready']))
            ->orderBy('created_at', 'desc')
            ->get();

        $historySprints = collect();
        if ($showClosed) {
            $historySprints = Sprint::whereHas('status', fn($q) => $q->whereIn('key', ['closed', 'archived']))
                ->orderBy('updated_at', 'desc')
                ->limit(10)
                ->get();
        }

        return view('sprints.board', compact('activeSprint', 'backlogSprints', 'historySprints', 'showClosed'));
    }

    /**
     * Update a sprint's status via Drag and Drop.
     */
    public function update(Request $request, Sprint $sprint)
    {
        $validated = $request->validate([
            'column' => 'required|in:backlog,active,history',
        ]);

        $column = $validated['column'];

        try {
            DB::transaction(function () use ($sprint, $column) {
                switch ($column) {
                    case 'active':
                        $this->makeActive($sprint);
                        break;
                    case 'history':
                        $this->makeClosed($sprint);
                        break;
                    case 'backlog':
                        $this->makeBacklog($sprint);
                        break;
                }
            });

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    private function makeActive(Sprint $sprint)
    {
        // 1. Find current active sprint
        $currentActive = Sprint::whereHas('status', fn($q) => $q->where('key', 'active'))
            ->where('id', '!=', $sprint->id)
            ->first();

        // 2. Demote current active to Ready
        if ($currentActive) {
            $readyStatus = SprintStatus::where('key', 'ready')->firstOrFail();
            $currentActive->update(['sprint_status_id' => $readyStatus->id]);
        }

        // 3. Promote target to Active
        $activeStatus = SprintStatus::where('key', 'active')->firstOrFail();
        $sprint->update(['sprint_status_id' => $activeStatus->id]);
    }

    private function makeClosed(Sprint $sprint)
    {
        $closedStatus = SprintStatus::where('key', 'closed')->firstOrFail();
        $sprint->update(['sprint_status_id' => $closedStatus->id]);
    }

    private function makeBacklog(Sprint $sprint)
    {
        // Default to Ready when moving back to backlog
        $readyStatus = SprintStatus::where('key', 'ready')->firstOrFail();
        $sprint->update(['sprint_status_id' => $readyStatus->id]);
    }
}
