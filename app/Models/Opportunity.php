<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Opportunity extends Model
{
    protected $fillable = [
        'title',
        'company',
        'type',
        'status',
        'score',
        'notes',
    ];

    public function actions(): HasMany
    {
        return $this->hasMany(Action::class);
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class)
            ->withPivot(['id', 'relationship_type', 'notes'])
            ->withTimestamps();
    }

    protected function casts(): array
    {
        return [
            'score' => 'integer',
        ];
    }
}
