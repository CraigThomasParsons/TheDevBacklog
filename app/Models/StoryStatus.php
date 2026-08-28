<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoryStatus extends Model
{
    protected $fillable = ['key', 'name'];

    public function stories(): HasMany
    {
        return $this->hasMany(Story::class);
    }

    public static function byKey(string $key): ?self
    {
        return static::where('key', $key)->first();
    }
}
