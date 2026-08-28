<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WritersRoom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Receives inception lifecycle webhooks from ChatProjects.
 *
 * When ChatProjects fires InceptionCompleted, it POSTs here so TheDevBacklog
 * can open a WritersRoom session and begin tracking the Epic/Story pipeline.
 */
final class InceptionWebhookController extends Controller
{
    /**
     * Record a completed inception and open a pending WritersRoom session.
     *
     * POST /api/inception/completed
     */
    public function completed(Request $request): JsonResponse
    {
        $this->authorizeRequest($request);

        $validated = $request->validate([
            'project_id'   => ['required', 'integer', 'exists:projects,id'],
            'inception_id' => ['required', 'integer'],
        ]);

        // Idempotent: one active WritersRoom session per project+inception pair.
        $room = WritersRoom::firstOrCreate(
            [
                'project_id'   => $validated['project_id'],
                'inception_id' => $validated['inception_id'],
            ],
            [
                'status' => WritersRoom::STATUS_PENDING,
            ]
        );

        return response()->json([
            'status'         => 'ok',
            'writers_room_id' => $room->id,
            'created'        => $room->wasRecentlyCreated,
        ], $room->wasRecentlyCreated ? 201 : 200);
    }

    private function authorizeRequest(Request $request): void
    {
        $token = trim((string) config('services.chatprojects.webhook_token'));

        if ($token === '') {
            abort(500, 'ChatProjects webhook token not configured.');
        }

        $provided = $request->bearerToken() ?? $request->header('X-ChatProjects-Token');

        if (! is_string($provided) || $provided !== $token) {
            abort(403, 'Invalid token.');
        }
    }
}
