<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Epic extends Model
{
    protected $fillable = [
        'title',
        'description',
        'status',
    ];

    public function stories(): HasMany
    {
        return $this->hasMany(Story::class);
    }
}
