<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Epic extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'summary',
        'epic_status_id',
    ];

    public function status(): BelongsTo
    {
        return $this->belongsTo(EpicStatus::class, 'epic_status_id');
    }

    public function stories(): HasMany
    {
        return $this->hasMany(Story::class);
    }

    public function getStoryCountAttribute(): int
    {
        return $this->stories()->count();
    }

    public function getDoneStoryCountAttribute(): int
    {
        return $this->stories()
            ->whereHas('status', fn ($q) => $q->where('key', 'done'))
            ->count();
    }

    public function getProgressPercentAttribute(): int
    {
        $total = $this->story_count;
        if ($total === 0) return 0;
        return (int) round(($this->done_story_count / $total) * 100);
    }
}
