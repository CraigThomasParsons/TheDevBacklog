<?php

namespace App\Http\Controllers;

use App\Models\MasonRunControl;
use App\Support\MasonRunState;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MasonRunStateController extends Controller
{
    public function index(MasonRunState $masonRunState): View
    {
        return view('mason.state', [
            'state' => $masonRunState->snapshot(),
        ]);
    }

    public function start(): RedirectResponse
    {
        $runControl = MasonRunControl::singleton();

        if ($runControl->is_running) {
            return redirect()
                ->route('mason.state')
                ->with('success', 'Sprint is already running.');
        }

        $runControl->is_running = true;
        $runControl->started_at = now();
        $runControl->stopped_at = null;
        $runControl->last_status_message = 'Sprint run requested from DevBacklog UI.';
        $runControl->save();

        return redirect()
            ->route('mason.state')
            ->with('success', 'Start Sprint requested. Mason will pick the next story.');
    }

    public function stop(): RedirectResponse
    {
        $runControl = MasonRunControl::singleton();
        $runControl->is_running = false;
        $runControl->stopped_at = now();
        $runControl->last_status_message = 'Sprint run stopped from DevBacklog UI.';
        $runControl->save();

        return redirect()
            ->route('mason.state')
            ->with('success', 'Sprint stopped.');
    }
}
