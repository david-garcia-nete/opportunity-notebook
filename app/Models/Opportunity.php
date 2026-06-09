<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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

    protected function casts(): array
    {
        return [
            'score' => 'integer',
        ];
    }
}
