<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EpicStatus extends Model
{
    protected $fillable = ['key', 'name'];

    public function epics(): HasMany
    {
        return $this->hasMany(Epic::class);
    }

    public static function byKey(string $key): ?self
    {
        return static::where('key', $key)->first();
    }
}
