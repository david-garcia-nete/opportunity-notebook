<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Contact extends Model
{
    protected $fillable = [
        'name',
        'organization',
        'email',
        'phone',
        'notes',
    ];

    public function opportunities(): BelongsToMany
    {
        return $this->belongsToMany(Opportunity::class)
            ->withPivot(['id', 'relationship_type', 'notes'])
            ->withTimestamps();
    }
}
