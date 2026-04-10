<?php

namespace App\Http\Controllers;

use App\Models\MasonRunControl;
use App\Support\MasonRunState;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MasonRunStateController extends Controller
{
    public function index(MasonRunState $masonRunState): View
    {
        return view('mason.state', [
            'state' => $masonRunState->snapshot(),
            'providerOptions' => config('mason.provider_options', []),
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

    public function updateProvider(Request $request): RedirectResponse
    {
        $providerOptions = config('mason.provider_options', []);
        $keys = array_keys($providerOptions);

        $validated = $request->validate([
            'provider_override' => 'required|string|in:' . implode(',', $keys),
        ]);

        $runControl = MasonRunControl::singleton();
        $provider = $validated['provider_override'];
        $runControl->provider_override = $provider === 'auto' ? null : $provider;
        $runControl->save();

        $label = $providerOptions[$provider] ?? $provider;

        return redirect()
            ->route('mason.state')
            ->with('success', "Provider mode updated to {$label}.");
    }

}
