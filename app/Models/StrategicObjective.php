<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class StrategicObjective extends Model
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

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'active' => 'boolean',
        ];
    }
}
