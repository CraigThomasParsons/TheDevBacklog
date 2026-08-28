<?php

namespace App\Console\Commands;

use App\Models\Epic;
use App\Models\EpicStatus;
use App\Models\Persona;
use App\Models\Story;
use App\Models\StoryStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Syncs ready stories from WritersRoom to TheDevBacklog
 * 
 * This command pulls stories with status="ready" from WritersRoom
 * and creates/updates them in DevBacklog for sprint planning.
 */
class SyncStoriesCommand extends Command
{
    protected $signature = 'ccdf:sync-stories 
                            {--project= : Only sync stories from specific project ID}
                            {--all : Sync all ready stories}
                            {--include-in-progress : Also sync in_progress stories}';

    protected $description = 'Sync ready stories from WritersRoom to DevBacklog';

    public function handle(): int
    {
        $this->info('Syncing stories from WritersRoom...');

        try {
            // Guard clause: syncing epics requires at least one local epic status.
            $defaultEpicStatus = EpicStatus::byKey('active') ?? EpicStatus::query()->first();
            if (! $defaultEpicStatus) {
                $this->error('No epic statuses found locally. Seed statuses first.');
                return self::FAILURE;
            }

            // Build query for WritersRoom stories
            $query = DB::connection('writersroom')
                ->table('stories')
                ->join('story_statuses', 'stories.story_status_id', '=', 'story_statuses.id')
                ->leftJoin('epics', 'stories.epic_id', '=', 'epics.id')
                ->leftJoin('personas', 'stories.persona_id', '=', 'personas.id')
                ->select([
                    'stories.id',
                    'stories.title',
                    'stories.narrative',
                    'stories.acceptance_criteria',
                    'stories.priority',
                    'stories.est_points',
                    'stories.created_at',
                    'stories.updated_at',
                    'story_statuses.key as status_key',
                    'epics.id as epic_id',
                    'epics.title as epic_title',
                    'epics.summary as epic_summary',
                    'epics.chat_project_id',
                    'personas.id as persona_id',
                    'personas.key as persona_key',
                    'personas.name as persona_name',
                ]);

            // Filter by status
            $statuses = ['ready'];
            if ($this->option('include-in-progress')) {
                $statuses[] = 'in_progress';
            }
            $query->whereIn('story_statuses.key', $statuses);

            // Filter by project if specified
            if ($projectId = $this->option('project')) {
                $query->where('epics.chat_project_id', $projectId);
            }

            $remoteStories = $query->get();

            if ($remoteStories->isEmpty()) {
                $this->warn('No ready stories found in WritersRoom.');
                return self::SUCCESS;
            }

            $this->info("Found {$remoteStories->count()} stories to sync.");

            $synced = 0;
            $created = 0;
            $updated = 0;

            foreach ($remoteStories as $remoteStory) {
                // Ensure we have the epic locally
                $epic = $this->syncEpic($remoteStory, (int) $defaultEpicStatus->id);
                
                // Ensure we have the persona locally
                $persona = $this->syncPersona($remoteStory);

                // Map status key to local status ID
                $localStatus = StoryStatus::byKey($remoteStory->status_key);
                if (!$localStatus) {
                    $this->error("Status '{$remoteStory->status_key}' not found locally. Run StatusSeeder.");
                    continue;
                }

                // Create or update the story
                $story = Story::updateOrCreate(
                    ['id' => $remoteStory->id], // Use same ID as WritersRoom
                    [
                        'title' => $remoteStory->title,
                        'narrative' => $remoteStory->narrative,
                        'acceptance_criteria' => $remoteStory->acceptance_criteria,
                        'epic_id' => $epic?->id,
                        'persona_id' => $persona?->id,
                        'story_status_id' => $localStatus->id,
                        'priority' => $remoteStory->priority,
                        'est_points' => $remoteStory->est_points,
                    ]
                );

                if ($story->wasRecentlyCreated) {
                    $created++;
                    $this->line("  ✅ Created: [{$story->id}] {$story->title}");
                } else {
                    $updated++;
                    $this->line("  🔄 Updated: [{$story->id}] {$story->title}");
                }
                
                $synced++;
            }

            // Consolidate historical duplicate epics so epic drafts stay clean.
            $mergedEpics = $this->consolidateDuplicateEpics();

            $this->newLine();
            $this->info("Sync complete: {$synced} stories ({$created} created, {$updated} updated)");
            if ($mergedEpics > 0) {
                $this->info("Consolidated {$mergedEpics} duplicate epic record(s).");
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to sync stories: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return self::FAILURE;
        }
    }

    /**
     * Sync epic from WritersRoom to DevBacklog
     */
    protected function syncEpic(object $remoteStory, int $epicStatusId): ?Epic
    {
        if (!$remoteStory->epic_id) {
            return null;
        }

        // Prefer stable project+title matching to avoid duplicate epics with the same scope.
        $epic = Epic::query()
            ->where('chat_project_id', $remoteStory->chat_project_id)
            ->where('title', $remoteStory->epic_title)
            ->first();

        if (! $epic) {
            $epic = Epic::find((int) $remoteStory->epic_id);
        }

        if ($epic) {
            $epic->update([
                'title' => $remoteStory->epic_title,
                'summary' => $remoteStory->epic_summary,
                'epic_status_id' => $epicStatusId,
                'chat_project_id' => $remoteStory->chat_project_id,
            ]);

            return $epic;
        }

        return Epic::create([
            'title' => $remoteStory->epic_title,
            'summary' => $remoteStory->epic_summary,
            'epic_status_id' => $epicStatusId,
            'chat_project_id' => $remoteStory->chat_project_id,
        ]);
    }

    /**
     * Sync persona from WritersRoom to DevBacklog
     */
    protected function syncPersona(object $remoteStory): ?Persona
    {
        if (!$remoteStory->persona_id) {
            return null;
        }

        // Persona key is globally unique; sync by key to avoid duplicate-key
        // collisions when local IDs differ from WritersRoom IDs.
        return Persona::updateOrCreate(
            ['key' => $remoteStory->persona_key],
            [
                'name' => $remoteStory->persona_name,
                'is_active' => true,
            ]
        );
    }

    /**
     * Merge duplicate epics by (chat_project_id, title), keeping the oldest id.
     */
    protected function consolidateDuplicateEpics(): int
    {
        $duplicateGroups = Epic::query()
            ->select('chat_project_id', 'title', DB::raw('COUNT(*) as duplicate_count'))
            ->whereNotNull('chat_project_id')
            ->groupBy('chat_project_id', 'title')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $deletedCount = 0;

        foreach ($duplicateGroups as $group) {
            $epics = Epic::query()
                ->where('chat_project_id', $group->chat_project_id)
                ->where('title', $group->title)
                ->orderBy('id')
                ->get();

            $canonicalEpic = $epics->first();
            if (! $canonicalEpic) {
                continue;
            }

            foreach ($epics->skip(1) as $duplicateEpic) {
                // Re-link stories so sprint/backlog views resolve to one epic record.
                Story::query()
                    ->where('epic_id', $duplicateEpic->id)
                    ->update(['epic_id' => $canonicalEpic->id]);

                $duplicateEpic->delete();
                $deletedCount++;
            }
        }

        return $deletedCount;
    }
}
