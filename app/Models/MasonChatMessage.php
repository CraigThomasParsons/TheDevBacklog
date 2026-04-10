<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasonChatMessage extends Model
{
    protected $fillable = [
        'sender',
        'status',
        'body',
        'in_reply_to_id',
        'related_story_id',
        'metadata',
        'answered_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'answered_at' => 'datetime',
    ];

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'in_reply_to_id');
    }
}
