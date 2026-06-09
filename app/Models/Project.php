<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Project extends Model
{
    protected $fillable = [
        'name',
        'url',
        'description',
        'status',
    ];

    public function opportunities(): BelongsToMany
    {
        return $this->belongsToMany(Opportunity::class)
            ->withPivot('notes')
            ->withTimestamps();
    }
}
