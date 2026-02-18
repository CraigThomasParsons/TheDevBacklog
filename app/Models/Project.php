<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class Project extends Model
{
    use HasFactory;

    /**
     * Cache projection column existence checks to avoid repeated schema queries.
     *
     * @var array<string, bool>
     */
    private static array $projectionColumnCache = [];

    /**
     * Project IDs are sourced externally from the Projects registry.
     */
    public $incrementing = false;

    /**
     * The primary key type is unsigned bigint.
     */
    protected $keyType = 'int';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'id',
        'project_uuid',
        'name',
        'description',
        'code_folder',
        'local_location',
        'github_repo',
        'gitea_location',
        'framework_description',
        'languages',
        'source_updated_at',
        'last_synced_at',
        'sync_hash',
        'deleted_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'source_updated_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Upsert projection data while ignoring stale source updates.
     *
     * @return array{project: self, stale: bool}
     */
    public static function syncFromRegistry(array $payload): array
    {
        $sourceUpdatedAt = isset($payload['source_updated_at']) && $payload['source_updated_at']
            ? Carbon::parse((string) $payload['source_updated_at'])
            : null;

        $project = static::query()->find((int) $payload['id']);

        if ($project === null) {
            $project = new static();
            $project->id = (int) $payload['id'];
        }

        // Reject stale updates to preserve newest canonical state.
        if (
            $project->exists
            && $sourceUpdatedAt !== null
            && static::hasProjectionColumn('source_updated_at')
            && $project->source_updated_at !== null
            && $sourceUpdatedAt->lt($project->source_updated_at)
        ) {
            if (static::hasProjectionColumn('last_synced_at')) {
                $project->forceFill([
                    'last_synced_at' => now(),
                ])->save();
            }

            return [
                'project' => $project,
                'stale' => true,
            ];
        }

        $effectivePayload = [
            'id' => (int) $project->id,
            'project_uuid' => $payload['project_uuid'] ?? $project->project_uuid,
            'name' => (string) ($payload['name'] ?? $project->name ?? ('Project ' . $project->id)),
            'description' => $payload['description'] ?? $project->description,
            'code_folder' => $payload['code_folder'] ?? $project->code_folder,
            'local_location' => $payload['local_location'] ?? $project->local_location,
            'github_repo' => $payload['github_repo'] ?? $project->github_repo,
            'gitea_location' => $payload['gitea_location'] ?? $project->gitea_location,
            'framework_description' => $payload['framework_description'] ?? $project->framework_description,
            'languages' => $payload['languages'] ?? $project->languages,
        ];

        if (static::hasProjectionColumn('deleted_at')) {
            $effectivePayload['deleted_at'] = $payload['deleted_at'] ?? $project->deleted_at?->toIso8601String();
        }

        $syncHash = hash('sha256', json_encode($effectivePayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $projectUpdatePayload = [
            'project_uuid' => $effectivePayload['project_uuid'],
            'name' => $effectivePayload['name'],
            'description' => $effectivePayload['description'],
            'code_folder' => $effectivePayload['code_folder'],
            'local_location' => $effectivePayload['local_location'],
            'github_repo' => $effectivePayload['github_repo'],
            'gitea_location' => $effectivePayload['gitea_location'],
            'framework_description' => $effectivePayload['framework_description'],
            'languages' => $effectivePayload['languages'],
        ];

        if (static::hasProjectionColumn('source_updated_at')) {
            $projectUpdatePayload['source_updated_at'] = $sourceUpdatedAt;
        }

        if (static::hasProjectionColumn('last_synced_at')) {
            $projectUpdatePayload['last_synced_at'] = now();
        }

        if (static::hasProjectionColumn('sync_hash')) {
            $projectUpdatePayload['sync_hash'] = $syncHash;
        }

        if (static::hasProjectionColumn('deleted_at')) {
            $projectUpdatePayload['deleted_at'] = isset($effectivePayload['deleted_at']) && $effectivePayload['deleted_at']
                ? Carbon::parse((string) $effectivePayload['deleted_at'])
                : null;
        }

        $project->forceFill($projectUpdatePayload)->save();

        return [
            'project' => $project,
            'stale' => false,
        ];
    }

    /**
     * Scope active projects (exclude soft-deleted source records).
     */
    public function scopeActive($query)
    {
        // Backward compatibility: older projection tables may not have deleted_at yet.
        if (! static::hasProjectionColumn('deleted_at')) {
            return $query;
        }

        return $query->whereNull('deleted_at');
    }

    /**
     * Check if the projection table includes the requested column.
     */
    private static function hasProjectionColumn(string $column): bool
    {
        if (array_key_exists($column, static::$projectionColumnCache)) {
            return static::$projectionColumnCache[$column];
        }

        $tableName = (new static())->getTable();

        static::$projectionColumnCache[$column] = Schema::hasColumn($tableName, $column);

        return static::$projectionColumnCache[$column];
    }

    /**
     * Epics that belong to this project by source project ID.
     */
    public function epics(): HasMany
    {
        return $this->hasMany(Epic::class, 'chat_project_id');
    }
}
