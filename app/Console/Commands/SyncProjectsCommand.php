<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\ProjectProjectionSyncEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;

class SyncProjectsCommand extends Command
{
    protected $signature = 'ccdf:sync-projects
                            {--project= : Sync a specific project by source ID}';

    protected $description = 'Sync project projection data from the Projects API into local DevBacklog projects table';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Syncing projects from Projects API...');

        try {
            $singleProjectId = $this->option('project');
            $items = $singleProjectId
                ? $this->fetchSingleProject((string) $singleProjectId)
                : $this->fetchAllProjects();

            if ($items === []) {
                $this->warn('No projects returned from the registry API.');
                return self::SUCCESS;
            }

            $createdCount = 0;
            $updatedCount = 0;
            $staleCount = 0;
            $now = now();

            foreach ($items as $item) {
                $projectId = (int) Arr::get($item, 'id');

                if ($projectId <= 0) {
                    continue;
                }

                $payload = [
                    'id' => $projectId,
                    'project_uuid' => Arr::get($item, 'project_uuid') ?? Arr::get($item, 'uuid'),
                    'name' => (string) Arr::get($item, 'name', ''),
                    'description' => Arr::get($item, 'description'),
                    'code_folder' => Arr::get($item, 'code_folder'),
                    'local_location' => Arr::get($item, 'local_location'),
                    'github_repo' => Arr::get($item, 'github_repo'),
                    'gitea_location' => Arr::get($item, 'gitea_location'),
                    'framework_description' => Arr::get($item, 'framework_description'),
                    'languages' => Arr::get($item, 'languages'),
                    'source_updated_at' => $this->parseNullableTimestamp(Arr::get($item, 'updated_at')),
                    'deleted_at' => $this->parseNullableTimestamp(Arr::get($item, 'deleted_at')),
                ];

                $syncEvent = ProjectProjectionSyncEvent::query()->create([
                    'source' => 'reconciliation',
                    'event_type' => 'project.upsert',
                    'project_id' => $projectId,
                    'project_uuid' => $payload['project_uuid'],
                    'status' => 'received',
                    'payload' => $item,
                    'received_at' => $now,
                ]);

                try {
                    $existing = Project::find($projectId);
                    $syncResult = Project::syncFromRegistry($payload);

                    if ($syncResult['stale']) {
                        $staleCount++;
                    }

                    $syncEvent->forceFill([
                        'status' => $syncResult['stale'] ? 'stale' : 'processed',
                        'processed_at' => now(),
                    ])->save();

                    if ($existing === null) {
                        $createdCount++;
                    } else {
                        $updatedCount++;
                    }
                } catch (\Throwable $throwable) {
                    $syncEvent->forceFill([
                        'status' => 'failed',
                        'error_message' => $throwable->getMessage(),
                        'processed_at' => now(),
                    ])->save();

                    throw $throwable;
                }
            }

            $this->info(
                "✓ Synced " . count($items)
                . " projects ({$createdCount} created, {$updatedCount} updated, {$staleCount} stale ignored)"
            );

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error('Failed to sync projects: ' . $exception->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Fetch all projects from the registry API.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchAllProjects(): array
    {
        $baseUrl = rtrim((string) config('services.projects_registry.base_url'), '/');

        if ($baseUrl === '') {
            throw new \RuntimeException('PROJECTS_API_BASE_URL is not configured.');
        }

        $allItems = [];
        $nextUrl = $baseUrl . '/api/projects';

        while ($nextUrl !== null) {
            $responseData = $this->getJson($nextUrl);

            $items = Arr::get($responseData, 'data');
            if (! is_array($items)) {
                // Support non-paginated list endpoints that return top-level arrays.
                $items = is_array($responseData) && array_is_list($responseData) ? $responseData : [];
            }

            $allItems = array_merge($allItems, $items);

            $next = Arr::get($responseData, 'links.next') ?? Arr::get($responseData, 'next_page_url');
            $nextUrl = is_string($next) && trim($next) !== '' ? $next : null;
        }

        return $allItems;
    }

    /**
     * Fetch a single project from the registry API.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchSingleProject(string $projectId): array
    {
        // Use list endpoint + local filter so single-project sync still works
        // even if the upstream detail endpoint has strict UUID-only parsing.
        $projects = $this->fetchAllProjects();

        return array_values(array_filter($projects, function (array $item) use ($projectId) {
            return (string) Arr::get($item, 'id') === $projectId
                || (string) Arr::get($item, 'project_uuid') === $projectId
                || (string) Arr::get($item, 'uuid') === $projectId;
        }));
    }

    /**
     * Perform authenticated JSON GET request against registry API.
     *
     * @return array<string, mixed>
     */
    private function getJson(string $url): array
    {
        $timeout = (int) config('services.projects_registry.timeout_seconds', 15);
        $token = trim((string) config('services.projects_registry.token'));

        $request = Http::acceptJson()->timeout($timeout);

        if ($token !== '') {
            $request = $request->withToken($token);
        }

        $response = $request->get($url);

        if ($response->failed()) {
            throw new \RuntimeException('Projects API request failed: ' . $response->status() . ' ' . $response->body());
        }

        $decoded = $response->json();

        if (! is_array($decoded)) {
            throw new \RuntimeException('Projects API response was not valid JSON object/array.');
        }

        return $decoded;
    }

    /**
     * Parse nullable timestamp values.
     */
    private function parseNullableTimestamp(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return Carbon::parse($value);
    }
}
