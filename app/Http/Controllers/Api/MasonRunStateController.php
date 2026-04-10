<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MasonRunControl;
use App\Support\MasonRunState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MasonRunStateController extends Controller
{
    public function show(MasonRunState $masonRunState): JsonResponse
    {
        return response()->json([
            'success' => true,
            'state' => $masonRunState->snapshot(),
        ]);
    }

    public function start(Request $request, MasonRunState $masonRunState): JsonResponse
    {
        $runControl = MasonRunControl::singleton();

        if ($runControl->is_running) {
            return response()->json([
                'success' => true,
                'message' => 'Sprint is already running',
                'already_running' => true,
                'state' => $masonRunState->snapshot(),
            ]);
        }

        $runControl->is_running = true;
        $runControl->started_at = now();
        $runControl->stopped_at = null;
        $runControl->last_status_message = 'Sprint run requested from DevBacklog UI.';
        $runControl->save();

        return response()->json([
            'success' => true,
            'message' => 'Sprint run started',
            'state' => $masonRunState->snapshot(),
        ]);
    }

    public function stop(MasonRunState $masonRunState): JsonResponse
    {
        $runControl = MasonRunControl::singleton();
        $runControl->is_running = false;
        $runControl->stopped_at = now();
        $runControl->last_status_message = 'Sprint run stopped from DevBacklog UI.';
        $runControl->save();

        return response()->json([
            'success' => true,
            'message' => 'Sprint run stopped',
            'state' => $masonRunState->snapshot(),
        ]);
    }

    public function heartbeat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_story_id' => 'nullable|integer|min:1',
            'status_message' => 'nullable|string|max:255',
            'payload' => 'nullable|array',
        ]);

        $runControl = MasonRunControl::singleton();
        $runControl->last_heartbeat_at = now();
        $runControl->current_story_id = $validated['current_story_id'] ?? null;
        $runControl->last_status_message = $validated['status_message'] ?? null;
        $runControl->heartbeat_payload = $validated['payload'] ?? [];
        $runControl->save();

        return response()->json([
            'success' => true,
        ]);
    }

    public function updateProvider(Request $request, MasonRunState $masonRunState): JsonResponse
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

        return response()->json([
            'success' => true,
            'message' => 'Provider mode updated',
            'state' => $masonRunState->snapshot(),
        ]);
    }

}
