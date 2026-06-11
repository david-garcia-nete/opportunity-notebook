<?php

namespace App\Models;

use App\Support\Statuses;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Project extends Model
{

    public function setStatusAttribute(?string $value): void
    {
        $this->attributes['status'] = Statuses::normalizeProject($value) ?? $value;
    }

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
