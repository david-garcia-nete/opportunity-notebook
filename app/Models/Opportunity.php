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
        'income_potential',
        'probability_of_success',
        'time_to_revenue',
        'strategic_alignment',
        'personal_interest',
        'skill_growth',
        'family_fit',
        'risk_level',
        'notes',
    ];

    public const EVALUATION_FIELDS = [
        'income_potential' => 'Income Potential',
        'probability_of_success' => 'Probability of Success',
        'time_to_revenue' => 'Time to Revenue',
        'strategic_alignment' => 'Strategic Alignment',
        'personal_interest' => 'Personal Interest',
        'skill_growth' => 'Skill Growth',
        'family_fit' => 'Family Fit',
        'risk_level' => 'Risk Level',
    ];

    public function computedScore(): ?int
    {
        $positiveFactors = [
            'income_potential',
            'probability_of_success',
            'strategic_alignment',
            'personal_interest',
            'skill_growth',
            'family_fit',
        ];

        $adjustmentFactors = [
            'time_to_revenue',
            'risk_level',
        ];

        $hasEvaluation = collect([...$positiveFactors, ...$adjustmentFactors])
            ->contains(fn (string $field) => $this->{$field} !== null);

        if (! $hasEvaluation) {
            return null;
        }

        // Simple foundation formula: add the positive 1-10 dimensions, then subtract
        // time-to-revenue and risk because slower or riskier opportunities should rank lower.
        $positiveScore = collect($positiveFactors)->sum(fn (string $field) => $this->{$field} ?? 0);
        $adjustmentScore = collect($adjustmentFactors)->sum(fn (string $field) => $this->{$field} ?? 0);

        return $positiveScore - $adjustmentScore;
    }

    public function actions(): HasMany
    {
        return $this->hasMany(Action::class);
    }

    public function opportunityGaps(): HasMany
    {
        return $this->hasMany(OpportunityGap::class);
    }

    public function openOpportunityGaps(): HasMany
    {
        return $this->opportunityGaps()->where('status', 'Open');
    }

    public function incompleteActions(): HasMany
    {
        return $this->actions()->whereNull('completed_at');
    }

    public function nextAction(): ?Action
    {
        if ($this->relationLoaded('actions')) {
            return $this->actions
                ->filter(fn (Action $action) => $action->completed_at === null)
                ->sort(function (Action $first, Action $second) {
                    $firstHasNoDueDate = $first->due_date === null;
                    $secondHasNoDueDate = $second->due_date === null;

                    if ($firstHasNoDueDate !== $secondHasNoDueDate) {
                        return $firstHasNoDueDate <=> $secondHasNoDueDate;
                    }

                    if (! $firstHasNoDueDate && ! $secondHasNoDueDate) {
                        $dueDateComparison = $first->due_date->getTimestamp() <=> $second->due_date->getTimestamp();

                        if ($dueDateComparison !== 0) {
                            return $dueDateComparison;
                        }
                    }

                    return $first->id <=> $second->id;
                })
                ->first();
        }

        return $this->orderedIncompleteActions()->first();
    }

    public function isOpenForNextAction(): bool
    {
        $status = str($this->status ?? '')->lower()->trim()->toString();

        if ($status === '') {
            return true;
        }

        return ! str($status)->contains(['closed', 'rejected', 'archived', 'parked']);
    }

    public function missingNextAction(): bool
    {
        if (! $this->isOpenForNextAction()) {
            return false;
        }

        if ($this->relationLoaded('actions')) {
            return $this->actions->doesntContain(fn (Action $action) => $action->completed_at === null);
        }

        return ! $this->incompleteActions()->exists();
    }

    private function orderedIncompleteActions(): HasMany
    {
        return $this->actions()
            ->whereNull('completed_at')
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->orderBy('id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class)
            ->withPivot(['id', 'relationship_type', 'notes'])
            ->withTimestamps();
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)
            ->withPivot('notes')
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
            'score' => 'integer',
            'income_potential' => 'integer',
            'probability_of_success' => 'integer',
            'time_to_revenue' => 'integer',
            'strategic_alignment' => 'integer',
            'personal_interest' => 'integer',
            'skill_growth' => 'integer',
            'family_fit' => 'integer',
            'risk_level' => 'integer',
        ];
    }
}
