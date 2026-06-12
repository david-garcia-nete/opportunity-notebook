<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Theme extends Model
{
    protected $fillable = [
        'name',
        'description',
        'priority',
        'active',
    ];

    public function opportunities(): BelongsToMany
    {
        return $this->belongsToMany(Opportunity::class)
            ->withTimestamps();
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)
            ->withTimestamps();
    }

    public function strategicObjectives(): BelongsToMany
    {
        return $this->belongsToMany(StrategicObjective::class)
            ->withTimestamps();
    }

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'active' => 'boolean',
        ];
    }
}
