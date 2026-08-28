<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Stores webhook and reconciliation projection sync telemetry.
 */
class ProjectProjectionSyncEvent extends Model
{
    protected $fillable = [
        'source',
        'event_type',
        'project_id',
        'project_uuid',
        'idempotency_key',
        'status',
        'error_message',
        'payload',
        'received_at',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];
}
