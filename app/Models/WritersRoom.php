<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WritersRoom extends Model
{
    protected $fillable = [
        'project_id',
        'inception_id',
        'status',
        'raw_llm_response',
    ];

    // Status constants
    const STATUS_PENDING    = 'pending';
    const STATUS_GENERATING = 'generating';
    const STATUS_COMPLETED  = 'completed';
    const STATUS_FAILED     = 'failed';

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Epics that were generated from the same inception as this WritersRoom session.
     * Matched via inception_id (a shared reference to ChatProjects.inceptions).
     */
    public function epics(): HasMany
    {
        return $this->hasMany(Epic::class, 'inception_id', 'inception_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }
}
