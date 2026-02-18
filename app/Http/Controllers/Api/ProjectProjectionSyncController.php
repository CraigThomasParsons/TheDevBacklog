<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectProjectionSyncEvent;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Consumes canonical project projection webhooks from Projects.elasticgun.com.
 */
class ProjectProjectionSyncController extends Controller
{
    /**
     * Upsert/delete project projection records from registry webhook payload.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorizeRequest($request);

        $idempotencyKey = trim((string) $request->header('X-Idempotency-Key', ''));

        // Idempotency guard: return success for previously-seen events.
        if ($idempotencyKey !== '') {
            $existingEvent = ProjectProjectionSyncEvent::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existingEvent !== null) {
                return response()->json([
                    'status' => 'ok',
                    'duplicate' => true,
                    'event' => $existingEvent->event_type,
                ]);
            }
        }

        $validated = $request->validate([
            'event' => ['required', 'string', 'in:project.upsert,project.deleted'],
            'project' => ['required', 'array'],
            'project.id' => ['required', 'integer'],
            'project.project_uuid' => ['nullable', 'uuid'],
            'project.name' => ['nullable', 'string', 'max:255'],
            'project.description' => ['nullable', 'string'],
            'project.code_folder' => ['nullable', 'string', 'max:500'],
            'project.local_location' => ['nullable', 'string', 'max:500'],
            'project.github_repo' => ['nullable', 'string', 'max:500'],
            'project.gitea_location' => ['nullable', 'string', 'max:500'],
            'project.framework_description' => ['nullable', 'string'],
            'project.languages' => ['nullable', 'string'],
            'project.source_updated_at' => ['nullable', 'date'],
            'project.deleted_at' => ['nullable', 'date'],
        ]);

        $projectPayload = $validated['project'];

        // Ensure delete events always mark the projection as soft-deleted.
        if ($validated['event'] === 'project.deleted') {
            $projectPayload['deleted_at'] = $projectPayload['deleted_at'] ?? now()->toIso8601String();
        }

        $syncEvent = ProjectProjectionSyncEvent::query()->create([
            'source' => 'webhook',
            'event_type' => $validated['event'],
            'project_id' => $projectPayload['id'] ?? null,
            'project_uuid' => $projectPayload['project_uuid'] ?? null,
            'idempotency_key' => $idempotencyKey !== '' ? $idempotencyKey : null,
            'status' => 'received',
            'payload' => $validated,
            'received_at' => now(),
        ]);

        try {
            $existingProject = Project::query()->find((int) $projectPayload['id']);
            $incomingSourceUpdatedAt = isset($projectPayload['source_updated_at'])
                ? Carbon::parse((string) $projectPayload['source_updated_at'])
                : null;

            $syncResult = Project::syncFromRegistry($projectPayload);

            $wasStale = $syncResult['stale']
                || (
                    $existingProject !== null
                    && $incomingSourceUpdatedAt !== null
                    && $existingProject->source_updated_at !== null
                    && $incomingSourceUpdatedAt->lt($existingProject->source_updated_at)
                );

            $syncEvent->forceFill([
                'status' => $wasStale ? 'stale' : 'processed',
                'processed_at' => now(),
            ])->save();

            return response()->json([
                'status' => 'ok',
                'project_id' => $syncResult['project']->id,
                'event' => $validated['event'],
                'stale' => $wasStale,
            ]);
        } catch (\Throwable $throwable) {
            $syncEvent->forceFill([
                'status' => 'failed',
                'error_message' => $throwable->getMessage(),
                'processed_at' => now(),
            ])->save();

            throw $throwable;
        }
    }

    /**
     * Authorize registry webhook call with shared token.
     */
    private function authorizeRequest(Request $request): void
    {
        $token = trim((string) config('services.projects_registry.webhook_token'));

        if ($token === '') {
            abort(500, 'Projects registry webhook token not configured.');
        }

        $providedToken = $request->bearerToken() ?? $request->header('X-Project-Registry-Token');

        if (! is_string($providedToken) || $providedToken !== $token) {
            abort(403, 'Invalid token.');
        }
    }
}
