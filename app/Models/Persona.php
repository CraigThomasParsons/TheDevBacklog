<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Persona extends Model
{
    protected $fillable = [
        'key',
        'name',
        'summary',
        'details',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function stories(): HasMany
    {
        return $this->hasMany(Story::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getStoryCountAttribute(): int
    {
        return $this->stories()->count();
    }
}
