<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Sprint extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'goal',
        'success_criteria',
        'sprint_status_id',
        'is_frozen',
        'frozen_at',
    ];

    protected $casts = [
        'is_frozen' => 'boolean',
        'frozen_at' => 'datetime',
    ];

    public function status(): BelongsTo
    {
        return $this->belongsTo(SprintStatus::class, 'sprint_status_id');
    }

    public function stories(): BelongsToMany
    {
        return $this->belongsToMany(Story::class, 'sprint_stories')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    public function freeze(): bool
    {
        if ($this->is_frozen) {
            return false;
        }

        $readyStatus = SprintStatus::byKey('ready');
        if ($readyStatus) {
            $this->sprint_status_id = $readyStatus->id;
        }

        $this->is_frozen = true;
        $this->frozen_at = now();

        return $this->save();
    }

    public function isFrozen(): bool
    {
        return $this->is_frozen;
    }

    public function getTotalPointsAttribute(): int
    {
        return $this->stories->sum('est_points') ?? 0;
    }

    public function getStoryCountAttribute(): int
    {
        return $this->stories->count();
    }

    protected static function boot()
    {
        parent::boot();

        static::updating(function ($sprint) {
            if ($sprint->getOriginal('is_frozen') && !$sprint->isDirty('sprint_status_id')) {
                // Allow status changes but block other field changes on frozen sprints
                $protectedFields = ['title', 'goal', 'success_criteria'];
                foreach ($protectedFields as $field) {
                    if ($sprint->isDirty($field)) {
                        throw new \Exception('Cannot modify a frozen sprint. Sprint context is immutable.');
                    }
                }
            }
        });
    }

    public function toSpecMarkdown(): string
    {
        $md = "# Sprint: {$this->title}\n\n";
        $md .= "## Goal\n{$this->goal}\n\n";

        if ($this->success_criteria) {
            $md .= "## Success Criteria\n{$this->success_criteria}\n\n";
        }

        $md .= "## Stories\n\n";

        foreach ($this->stories as $story) {
            $md .= "### {$story->title}\n\n";
            $md .= "**Narrative:** {$story->narrative}\n\n";
            if ($story->acceptance_criteria) {
                $md .= "**Acceptance Criteria:**\n{$story->acceptance_criteria}\n\n";
            }
            $md .= "---\n\n";
        }

        return $md;
    }
}
