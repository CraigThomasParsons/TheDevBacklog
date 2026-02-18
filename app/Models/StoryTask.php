<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoryTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'story_id',
        'external_task_id',
        'title',
        'description',
        'success_criteria',
        'constraints',
        'inputs',
        'expected_outputs',
        'mode',
        'priority',
        'sort_order',
        'max_attempts',
        'state',
        'last_provider',
        'last_run_status',
        'last_duration_ms',
        'last_synced_at',
        'raw_payload',
    ];

    protected $casts = [
        'success_criteria' => 'array',
        'constraints' => 'array',
        'inputs' => 'array',
        'expected_outputs' => 'array',
        'raw_payload' => 'array',
        'last_synced_at' => 'datetime',
        'priority' => 'integer',
        'sort_order' => 'integer',
        'max_attempts' => 'integer',
        'last_duration_ms' => 'integer',
    ];

    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }
}
