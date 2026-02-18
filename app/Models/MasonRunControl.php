<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasonRunControl extends Model
{
    protected $fillable = [
        'is_running',
        'started_at',
        'stopped_at',
        'last_heartbeat_at',
        'current_story_id',
        'last_status_message',
        'heartbeat_payload',
    ];

    protected $casts = [
        'is_running' => 'boolean',
        'started_at' => 'datetime',
        'stopped_at' => 'datetime',
        'last_heartbeat_at' => 'datetime',
        'heartbeat_payload' => 'array',
    ];

    public static function singleton(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            ['is_running' => false]
        );
    }
}
