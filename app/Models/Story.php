<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class Story extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'narrative',
        'acceptance_criteria',
        'epic_id',
        'persona_id',
        'story_status_id',
        'priority',
        'est_points',
    ];

    protected $casts = [
        'priority' => 'integer',
        'est_points' => 'integer',
    ];

    public function epic(): BelongsTo
    {
        return $this->belongsTo(Epic::class);
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(StoryStatus::class, 'story_status_id');
    }

    public function sprints(): BelongsToMany
    {
        return $this->belongsToMany(Sprint::class, 'sprint_stories')
            ->withPivot('sort_order');
    }

    public function scopeReady(Builder $query): Builder
    {
        return $query->whereHas('status', fn ($q) => $q->where('key', 'ready'));
    }

    public function scopeDone(Builder $query): Builder
    {
        return $query->whereHas('status', fn ($q) => $q->where('key', 'done'));
    }

    public function scopeByStatus(Builder $query, string $statusKey): Builder
    {
        return $query->whereHas('status', fn ($q) => $q->where('key', $statusKey));
    }

    public function isReady(): bool
    {
        return !empty($this->title) 
            && !empty($this->narrative) 
            && !empty($this->acceptance_criteria);
    }

    public function markReady(): bool
    {
        if (!$this->isReady()) {
            return false;
        }

        $readyStatus = StoryStatus::byKey('ready');
        if ($readyStatus) {
            $this->story_status_id = $readyStatus->id;
            return $this->save();
        }

        return false;
    }
}
