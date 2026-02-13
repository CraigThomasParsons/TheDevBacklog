<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SprintStatus extends Model
{
    protected $fillable = ['key', 'name'];

    public function sprints(): HasMany
    {
        return $this->hasMany(Sprint::class);
    }

    public static function byKey(string $key): ?self
    {
        return static::where('key', $key)->first();
    }
}
