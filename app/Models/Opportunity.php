<?php

namespace App\Models;

use App\Support\Statuses;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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
        'is_focus',
        'focused_at',
        'focus_reason',
        'outcome',
        'outcome_date',
        'outcome_reason',
        'outcome_notes',
        'lesson_learned',
        'notes',
    ];

    public const OUTCOMES = [
        'Won',
        'Lost',
        'Parked',
        'Abandoned',
        'No Response',
        'Not Pursued',
    ];

    public const FINAL_OUTCOMES = [
        'Won',
        'Lost',
        'Abandoned',
        'No Response',
        'Not Pursued',
    ];

    public const SUCCESS_OUTCOME_REASONS = [
        'strong_relationship' => 'Strong Relationship',
        'high_readiness' => 'High Readiness',
        'strong_fit' => 'Strong Fit',
        'timing' => 'Timing',
        'persistence' => 'Persistence',
        'referral' => 'Referral',
        'differentiated_offer' => 'Differentiated Offer',
        'other' => 'Other',
    ];

    public const FAILURE_OUTCOME_REASONS = [
        'insufficient_readiness' => 'Insufficient Readiness',
        'skill_gap' => 'Skill Gap',
        'relationship_gap' => 'Relationship Gap',
        'timing' => 'Timing',
        'competition' => 'Competition',
        'low_priority' => 'Low Priority',
        'capacity_constraint' => 'Capacity Constraint',
        'abandoned' => 'Abandoned',
        'no_response' => 'No Response',
        'other' => 'Other',
    ];

    public const OUTCOMES_WITH_LEARNING = [
        'Won',
        'Lost',
        'Abandoned',
        'No Response',
        'Not Pursued',
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

    public static function outcomeReasonOptions(): array
    {
        return collect(self::SUCCESS_OUTCOME_REASONS)
            ->merge(self::FAILURE_OUTCOME_REASONS)
            ->all();
    }

    public static function outcomeReasonOptionsFor(?string $outcome): array
    {
        return $outcome === 'Won'
            ? self::SUCCESS_OUTCOME_REASONS
            : (in_array($outcome, self::OUTCOMES_WITH_LEARNING, true) ? self::FAILURE_OUTCOME_REASONS : []);
    }

    public function outcomeReasonLabel(): ?string
    {
        if (! $this->outcome_reason) {
            return null;
        }

        return self::outcomeReasonOptionsFor($this->outcome)[$this->outcome_reason]
            ?? self::outcomeReasonOptions()[$this->outcome_reason]
            ?? $this->outcome_reason;
    }

    public function setStatusAttribute(?string $value): void
    {
        $this->attributes['status'] = Statuses::normalizeOpportunity($value) ?? $value;
    }

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

    public function weightedScore(?UserPreference $preference = null): ?int
    {
        $weightedFactors = $this->weightedScoreFactors($preference);

        if ($weightedFactors->isEmpty()) {
            return null;
        }

        $totalWeight = $weightedFactors->sum('weight');

        if ($totalWeight === 0) {
            return null;
        }

        $weightedValue = $weightedFactors->sum(fn (array $factor) => $factor['score'] * $factor['weight']);

        return (int) round(($weightedValue / $totalWeight) * 10);
    }

    public function weightedScoreContributors(?UserPreference $preference = null, int $limit = 3): Collection
    {
        return $this->weightedScoreFactors($preference)
            ->sortByDesc(fn (array $factor) => $factor['score'] * $factor['weight'])
            ->take($limit)
            ->pluck('label')
            ->values();
    }

    private function weightedScoreFactors(?UserPreference $preference = null): Collection
    {
        $preference ??= auth()->user()?->preference;

        if (! $preference) {
            return collect();
        }

        return collect([
            ['field' => 'income_potential', 'weight' => 'income_weight', 'label' => 'Income Potential'],
            ['field' => 'probability_of_success', 'weight' => 'probability_weight', 'label' => 'Probability of Success'],
            ['field' => 'time_to_revenue', 'weight' => 'time_to_revenue_weight', 'label' => 'Fast Time to Revenue', 'invert' => true],
            ['field' => 'strategic_alignment', 'weight' => 'strategic_alignment_weight', 'label' => 'Strategic Alignment'],
            ['field' => 'personal_interest', 'weight' => 'personal_interest_weight', 'label' => 'Personal Interest'],
            ['field' => 'skill_growth', 'weight' => 'skill_growth_weight', 'label' => 'Skill Growth'],
            ['field' => 'family_fit', 'weight' => 'family_fit_weight', 'label' => 'Family Fit'],
            ['field' => 'risk_level', 'weight' => 'risk_weight', 'label' => 'Lower Risk', 'invert' => true],
        ])->filter(fn (array $factor) => $this->{$factor['field']} !== null)
            ->map(function (array $factor) use ($preference) {
                $value = $this->{$factor['field']};

                return [
                    'label' => $factor['label'],
                    'score' => ($factor['invert'] ?? false) ? 11 - $value : $value,
                    'weight' => $preference->{$factor['weight']},
                ];
            })
            ->filter(fn (array $factor) => $factor['weight'] > 0)
            ->values();
    }

    public function themes(): BelongsToMany
    {
        return $this->belongsToMany(Theme::class)
            ->withTimestamps();
    }

    public function actions(): HasMany
    {
        return $this->hasMany(Action::class);
    }

    public function opportunityGaps(): HasMany
    {
        return $this->hasMany(OpportunityGap::class);
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(OpportunityDecision::class);
    }

    public function openOpportunityGaps(): HasMany
    {
        return $this->opportunityGaps()->where('status', Statuses::GAP_OPEN);
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
        if ($this->status === null || $this->status === '') {
            return true;
        }

        return ! in_array($this->status, Statuses::unavailableForNextActionOpportunities(), true);
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

    public function contactInteractions(): HasMany
    {
        return $this->hasMany(ContactInteraction::class);
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
            'is_focus' => 'boolean',
            'focused_at' => 'datetime',
            'outcome_date' => 'date',
        ];
    }
}
