<?php

namespace App\Services;

use App\Models\Opportunity;
use Illuminate\Support\Collection;

class OutcomeAnalyticsService
{
    public function counts(): Collection
    {
        $counts = Opportunity::query()
            ->whereNotNull('outcome')
            ->selectRaw('outcome, count(*) as aggregate')
            ->groupBy('outcome')
            ->pluck('aggregate', 'outcome');

        return collect(Opportunity::OUTCOMES)
            ->mapWithKeys(fn (string $outcome) => [$outcome => (int) ($counts[$outcome] ?? 0)]);
    }

    public function totalWithOutcomes(): int
    {
        return Opportunity::query()->whereNotNull('outcome')->count();
    }

    public function finalOutcomeCount(): int
    {
        return Opportunity::query()
            ->whereIn('outcome', Opportunity::FINAL_OUTCOMES)
            ->count();
    }

    public function winRate(): float
    {
        $finalOutcomeCount = $this->finalOutcomeCount();

        if ($finalOutcomeCount === 0) {
            return 0.0;
        }

        return round(($this->counts()['Won'] / $finalOutcomeCount) * 100, 1);
    }

    public function summary(): array
    {
        $counts = $this->counts();

        return [
            'total_with_outcomes' => $this->totalWithOutcomes(),
            'counts' => $counts,
            'win_rate' => $this->winRate(),
            'lost_combined_count' => $counts['Lost'] + $counts['Abandoned'] + $counts['No Response'],
        ];
    }

    public function breakdowns(): array
    {
        $opportunities = Opportunity::query()
            ->whereNotNull('outcome')
            ->with(['opportunityGaps', 'projects', 'strategicObjectives'])
            ->get();
        $readiness = app(OpportunityReadinessService::class);

        return [
            'Opportunity Type' => $this->singleValueBreakdown($opportunities, fn (Opportunity $opportunity) => $opportunity->type ?: 'No type'),
            'Status' => $this->singleValueBreakdown($opportunities, fn (Opportunity $opportunity) => $opportunity->status ?: 'No status'),
            'Focus Status' => $this->singleValueBreakdown($opportunities, fn (Opportunity $opportunity) => $opportunity->is_focus ? 'Focus' : 'Not Focus'),
            'Outcome Reason' => $this->singleValueBreakdown($opportunities, fn (Opportunity $opportunity) => $opportunity->outcomeReasonLabel() ?: 'No reason'),
            'Readiness Status' => $this->singleValueBreakdown($opportunities, fn (Opportunity $opportunity) => $readiness->statusForScore($readiness->score($opportunity))),
            'Strategic Objective' => $this->strategicObjectiveBreakdown($opportunities),
        ];
    }

    public function reasonBreakdownFor(string $outcome, int $limit = 5): Collection
    {
        $options = Opportunity::outcomeReasonOptionsFor($outcome);

        return Opportunity::query()
            ->where('outcome', $outcome)
            ->whereNotNull('outcome_reason')
            ->selectRaw('outcome_reason, count(*) as aggregate')
            ->groupBy('outcome_reason')
            ->orderByDesc('aggregate')
            ->orderBy('outcome_reason')
            ->take($limit)
            ->get()
            ->map(fn (Opportunity $opportunity) => [
                'reason' => $opportunity->outcome_reason,
                'label' => $options[$opportunity->outcome_reason]
                    ?? Opportunity::outcomeReasonOptions()[$opportunity->outcome_reason]
                    ?? $opportunity->outcome_reason,
                'count' => (int) $opportunity->aggregate,
            ]);
    }

    public function outcomeReasonBreakdowns(): array
    {
        return [
            'wins' => $this->reasonBreakdownFor('Won'),
            'losses' => $this->reasonBreakdownFor('Lost'),
            'abandonments' => $this->reasonBreakdownFor('Abandoned'),
            'no_responses' => $this->reasonBreakdownFor('No Response'),
        ];
    }

    public function recentLessons(int $limit = 5): Collection
    {
        return Opportunity::query()
            ->whereNotNull('lesson_learned')
            ->where('lesson_learned', '!=', '')
            ->orderByRaw('outcome_date is null')
            ->latest('outcome_date')
            ->latest()
            ->take($limit)
            ->get();
    }

    public function recentOutcomes(int $limit = 10): Collection
    {
        return Opportunity::query()
            ->whereNotNull('outcome')
            ->with(['opportunityGaps', 'projects'])
            ->orderByRaw('outcome_date is null')
            ->latest('outcome_date')
            ->latest()
            ->take($limit)
            ->get();
    }

    private function singleValueBreakdown(Collection $opportunities, callable $labelResolver): Collection
    {
        return $this->rows($opportunities->map(fn (Opportunity $opportunity) => [
            'label' => $labelResolver($opportunity),
            'outcome' => $opportunity->outcome,
        ]));
    }

    private function strategicObjectiveBreakdown(Collection $opportunities): Collection
    {
        $items = $opportunities->flatMap(function (Opportunity $opportunity) {
            if ($opportunity->strategicObjectives->isEmpty()) {
                return [[
                    'label' => 'No strategic objective',
                    'outcome' => $opportunity->outcome,
                ]];
            }

            return $opportunity->strategicObjectives->map(fn ($objective) => [
                'label' => $objective->name,
                'outcome' => $opportunity->outcome,
            ]);
        });

        return $this->rows($items);
    }

    private function rows(Collection $items): Collection
    {
        return $items
            ->groupBy('label')
            ->map(function (Collection $group, string $label) {
                $outcomeCounts = $group->countBy('outcome');

                return [
                    'label' => $label,
                    'total' => $group->count(),
                    'counts' => collect(Opportunity::OUTCOMES)
                        ->mapWithKeys(fn (string $outcome) => [$outcome => (int) ($outcomeCounts[$outcome] ?? 0)]),
                ];
            })
            ->sortByDesc('total')
            ->values();
    }
}
