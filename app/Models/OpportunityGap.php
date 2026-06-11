<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OpportunityGap extends Model
{
    public const CATEGORIES = [
        'Skill',
        'Experience',
        'Education',
        'Certification',
        'Portfolio',
        'Networking',
        'Financial',
        'Other',
    ];

    public const STATUSES = [
        'Open',
        'In Progress',
        'Complete',
    ];

    public const PRIORITIES = [
        'Critical',
        'High',
        'Medium',
        'Low',
    ];

    protected $fillable = [
        'opportunity_id',
        'title',
        'description',
        'category',
        'status',
        'priority',
    ];

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(Action::class);
    }

    public function openActions(): HasMany
    {
        return $this->actions()->whereNull('completed_at');
    }

    public function completedActions(): HasMany
    {
        return $this->actions()->whereNotNull('completed_at');
    }

    public function priorityRank(): int
    {
        $rank = array_search($this->priority, self::PRIORITIES, true);

        return $rank === false ? count(self::PRIORITIES) : $rank;
    }
}
