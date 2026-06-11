<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    protected $fillable = [
        'name',
        'organization',
        'email',
        'phone',
        'notes',
    ];

    public function contactInteractions(): HasMany
    {
        return $this->hasMany(ContactInteraction::class);
    }

    public function linkedOpportunitiesCount(): int
    {
        if ($this->relationLoaded('opportunities')) {
            return $this->opportunities->count();
        }

        return $this->opportunities()->count();
    }

    public function activeOpportunitiesCount(): int
    {
        $opportunities = $this->relationLoaded('opportunities')
            ? $this->opportunities
            : $this->opportunities()->get();

        return $opportunities
            ->filter(fn (Opportunity $opportunity) => $opportunity->isOpenForNextAction())
            ->count();
    }

    public function averageOpportunityScore(): ?float
    {
        $opportunities = $this->relationLoaded('opportunities')
            ? $this->opportunities
            : $this->opportunities()->get();

        $scoredOpportunities = $opportunities
            ->map(fn (Opportunity $opportunity) => $opportunity->computedScore())
            ->filter(fn (?int $score) => $score !== null);

        if ($scoredOpportunities->isEmpty()) {
            return null;
        }

        return round($scoredOpportunities->avg(), 1);
    }

    public function opportunities(): BelongsToMany
    {
        return $this->belongsToMany(Opportunity::class)
            ->withPivot(['id', 'relationship_type', 'notes'])
            ->withTimestamps();
    }
}
