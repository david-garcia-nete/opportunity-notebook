<?php

namespace App\Services;

use App\Models\Action;
use App\Models\Opportunity;
use App\Models\OpportunityGap;
use App\Models\UserPreference;
use App\Support\Statuses;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class OpportunityForecastService
{
    public function __construct(private OpportunityReadinessService $readiness)
    {
    }

    public function score(Opportunity $opportunity, ?UserPreference $preference = null): int
    {
        $weightedScore = $this->weightedScore($opportunity, $preference) ?? 0;
        $readinessScore = $this->readiness->score($opportunity);
        $executionHealth = $this->executionHealth($opportunity);

        return max(0, min(100, (int) round(
            ($weightedScore * 0.4)
            + ($readinessScore * 0.4)
            + ($executionHealth * 0.2)
        )));
    }

    public function status(Opportunity $opportunity, ?UserPreference $preference = null): string
    {
        return $this->statusForScore($this->score($opportunity, $preference));
    }

    public function statusForScore(int $score): string
    {
        return match (true) {
            $score >= 90 => 'Excellent',
            $score >= 75 => 'Strong',
            $score >= 60 => 'Moderate',
            $score >= 40 => 'At Risk',
            default => 'Unlikely',
        };
    }

    public function breakdown(Opportunity $opportunity, ?UserPreference $preference = null): Collection
    {
        $weightedScore = $this->weightedScore($opportunity, $preference) ?? 0;
        $readinessScore = $this->readiness->score($opportunity);
        $executionHealth = $this->executionHealth($opportunity);
        $forecastScore = $this->score($opportunity, $preference);

        return collect([
            [
                'label' => 'Weighted Score',
                'raw_score' => $weightedScore,
                'weight' => 40,
                'points' => (int) round($weightedScore * 0.4),
            ],
            [
                'label' => 'Readiness',
                'raw_score' => $readinessScore,
                'weight' => 40,
                'points' => (int) round($readinessScore * 0.4),
            ],
            [
                'label' => 'Execution',
                'raw_score' => $executionHealth,
                'weight' => 20,
                'points' => (int) round($executionHealth * 0.2),
            ],
            [
                'label' => 'Forecast',
                'raw_score' => $forecastScore,
                'weight' => 100,
                'points' => $forecastScore,
            ],
        ]);
    }

    public function executionHealth(Opportunity $opportunity): int
    {
        $penalty = ($this->overdueActions($opportunity)->count() * 10)
            + ($opportunity->missingNextAction() ? 15 : 0)
            + ($this->criticalGapsWithoutActionPlan($opportunity)->count() * 15)
            + ($this->highGapsWithoutActionPlan($opportunity)->count() * 10);

        return max(0, min(100, 100 - $penalty));
    }

    public function reasons(Opportunity $opportunity, ?UserPreference $preference = null): Collection
    {
        $reasons = collect();
        $overdueCount = $this->overdueActions($opportunity)->count();
        $readinessScore = $this->readiness->score($opportunity);
        $criticalWithoutPlanCount = $this->criticalGapsWithoutActionPlan($opportunity)->count();
        $highWithoutPlanCount = $this->highGapsWithoutActionPlan($opportunity)->count();

        if ($opportunity->missingNextAction()) {
            $reasons->push('Missing next action');
        }

        if ($readinessScore < 60) {
            $reasons->push('Low readiness');
        }

        if ($overdueCount >= 2) {
            $reasons->push('Multiple overdue actions');
        } elseif ($overdueCount === 1) {
            $reasons->push('Overdue action');
        }

        if ($criticalWithoutPlanCount > 0) {
            $reasons->push(Str::plural('Critical gap', $criticalWithoutPlanCount).' without action plan');
        }

        if ($highWithoutPlanCount > 0) {
            $reasons->push(Str::plural('High gap', $highWithoutPlanCount).' without action plan');
        }

        if ($this->weightedScore($opportunity, $preference) === null) {
            $reasons->push('Missing weighted evaluation');
        }

        return $reasons->values();
    }

    public function summaries(Collection $opportunities, ?UserPreference $preference = null): Collection
    {
        return $opportunities
            ->map(fn (Opportunity $opportunity) => [
                'opportunity' => $opportunity,
                'weighted_score' => $this->weightedScore($opportunity, $preference),
                'readiness_score' => $this->readiness->score($opportunity),
                'execution_health' => $this->executionHealth($opportunity),
                'forecast_score' => $this->score($opportunity, $preference),
                'forecast_status' => $this->status($opportunity, $preference),
                'focus_status' => $opportunity->is_focus ? 'Focus' : 'Not Focus',
                'reasons' => $this->reasons($opportunity, $preference),
            ])
            ->values();
    }

    public function ranked(Collection $opportunities, ?UserPreference $preference = null): Collection
    {
        return $this->summaries($opportunities, $preference)
            ->sortByDesc('forecast_score')
            ->values();
    }

    public function focusAtRisk(Collection $opportunities, ?UserPreference $preference = null): Collection
    {
        return $this->summaries($opportunities, $preference)
            ->filter(fn (array $summary) => $summary['opportunity']->is_focus && $summary['forecast_score'] < 60)
            ->sortBy('forecast_score')
            ->values();
    }

    public function weightedScore(Opportunity $opportunity, ?UserPreference $preference = null): ?int
    {
        $preference ??= auth()->user()?->preference ?? new UserPreference(UserPreference::defaults());

        return $opportunity->weightedScore($preference);
    }

    private function overdueActions(Opportunity $opportunity): Collection
    {
        return $this->actions($opportunity)
            ->filter(fn (Action $action) => $action->completed_at === null && $action->due_date !== null && $action->due_date->isBefore(today()))
            ->values();
    }

    private function criticalGapsWithoutActionPlan(Opportunity $opportunity): Collection
    {
        return $this->gapsWithoutActionPlan($opportunity)->where('priority', 'Critical')->values();
    }

    private function highGapsWithoutActionPlan(Opportunity $opportunity): Collection
    {
        return $this->gapsWithoutActionPlan($opportunity)->where('priority', 'High')->values();
    }

    private function gapsWithoutActionPlan(Opportunity $opportunity): Collection
    {
        return $this->gaps($opportunity)
            ->where('status', Statuses::GAP_OPEN)
            ->filter(fn (OpportunityGap $gap) => in_array($gap->priority, ['Critical', 'High'], true))
            ->filter(fn (OpportunityGap $gap) => $this->openGapActions($gap)->isEmpty())
            ->values();
    }

    private function actions(Opportunity $opportunity): Collection
    {
        if ($opportunity->relationLoaded('actions')) {
            return $opportunity->actions;
        }

        return $opportunity->actions()->get();
    }

    private function gaps(Opportunity $opportunity): Collection
    {
        if ($opportunity->relationLoaded('opportunityGaps')) {
            return $opportunity->opportunityGaps;
        }

        return $opportunity->opportunityGaps()->with('actions')->get();
    }

    private function openGapActions(OpportunityGap $gap): Collection
    {
        if ($gap->relationLoaded('actions')) {
            return $gap->actions->whereNull('completed_at')->values();
        }

        return $gap->actions()->whereNull('completed_at')->get();
    }
}
