<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeatureTest extends Model
{
    use HasFactory;

    protected $fillable = [
        'feature_id',
        'type',
        'title',
        'body',
        'is_required',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }
}
