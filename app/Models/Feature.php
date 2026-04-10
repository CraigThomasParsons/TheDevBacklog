<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feature extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'sprint_id',
        'story_id',
        'title',
        'description',
        'status',
        'priority',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'priority' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
    }

    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    public function tests(): HasMany
    {
        return $this->hasMany(FeatureTest::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(StoryTask::class, 'feature_id');
    }
}
