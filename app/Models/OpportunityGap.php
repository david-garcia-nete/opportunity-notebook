<?php

namespace App\Models;

use App\Support\Statuses;
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
        Statuses::GAP_OPEN,
        Statuses::GAP_IN_PROGRESS,
        Statuses::GAP_COMPLETE,
    ];

    public const PRIORITIES = [
        'Critical',
        'High',
        'Medium',
        'Low',
    ];


    public function setStatusAttribute(?string $value): void
    {
        $this->attributes['status'] = Statuses::normalizeGap($value) ?? $value;
    }

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
