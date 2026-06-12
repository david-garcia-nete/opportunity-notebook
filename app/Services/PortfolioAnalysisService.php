<?php

namespace App\Services;

use App\Models\Action;
use App\Models\Opportunity;
use App\Models\OpportunityGap;
use App\Models\StrategicObjective;
use App\Models\Theme;
use App\Models\UserPreference;
use App\Support\Statuses;
use Illuminate\Support\Collection;

class PortfolioAnalysisService
{
    public function __construct(
        private OpportunityForecastService $forecast,
        private OpportunityReadinessService $readiness,
    ) {
    }

    public function analysis(?UserPreference $preference = null): array
    {
        $opportunities = $this->opportunities();
        $summaries = $this->summaries($opportunities, $preference);
        $activeSummaries = $summaries->filter(fn (array $summary) => $this->isActive($summary['opportunity']))->values();
        $focusSummaries = $activeSummaries->filter(fn (array $summary) => $summary['opportunity']->is_focus)->values();
        $risks = $this->portfolioRisks($focusSummaries);

        return [
            'metrics' => $this->metrics($opportunities, $activeSummaries, $focusSummaries),
            'objectiveCoverage' => $this->objectiveCoverage($preference),
            'distributions' => $this->distributions($opportunities, $summaries),
            'focusOpportunities' => $focusSummaries->sortByDesc('forecast_score')->values(),
            'portfolioRisks' => $risks,
            'portfolioStrengths' => $this->portfolioStrengths($focusSummaries),
            'themeAnalysis' => $this->themeAnalysis(),
        ];
    }

    public function dashboardHealth(?UserPreference $preference = null): array
    {
        $analysis = $this->analysis($preference);

        return [
            'average_forecast_score' => $analysis['metrics']['average_forecast_score'],
            'average_readiness_score' => $analysis['metrics']['average_readiness_score'],
            'focused_opportunity_count' => $analysis['metrics']['focused_opportunity_count'],
            'portfolio_risk_count' => $analysis['portfolioRisks']->count(),
        ];
    }

    public function metrics(Collection $opportunities, Collection $activeSummaries, Collection $focusSummaries): array
    {
        return [
            'total_opportunities' => $opportunities->count(),
            'active_opportunities' => $activeSummaries->count(),
            'focused_opportunity_count' => $focusSummaries->count(),
            'forecasted_strong_opportunities' => $activeSummaries->where('forecast_score', '>=', 75)->count(),
            'forecasted_at_risk_opportunities' => $activeSummaries->where('forecast_score', '<', 60)->count(),
            'average_opportunity_score' => $this->average($activeSummaries->pluck('weighted_score')->filter(fn ($score) => $score !== null)),
            'average_readiness_score' => $this->average($activeSummaries->pluck('readiness_score')),
            'average_forecast_score' => $this->average($activeSummaries->pluck('forecast_score')),
        ];
    }

    public function objectiveCoverage(?UserPreference $preference = null): Collection
    {
        return StrategicObjective::query()
            ->with(['opportunities' => fn ($query) => $query->with(['actions', 'opportunityGaps.actions', 'projects'])])
            ->orderByDesc('priority')
            ->orderBy('name')
            ->get()
            ->map(function (StrategicObjective $objective) use ($preference) {
                $activeOpportunities = $objective->opportunities
                    ->filter(fn (Opportunity $opportunity) => $this->isActive($opportunity))
                    ->values();
                $summaries = $this->summaries($activeOpportunities, $preference);
                $activeCount = $activeOpportunities->count();

                return [
                    'objective' => $objective,
                    'priority' => $objective->priority,
                    'linked_opportunity_count' => $activeCount,
                    'focused_opportunity_count' => $activeOpportunities->where('is_focus', true)->count(),
                    'average_forecast_score' => $this->average($summaries->pluck('forecast_score')),
                    'average_readiness_score' => $this->average($summaries->pluck('readiness_score')),
                    'coverage' => match (true) {
                        $activeCount >= 3 => 'Strong Coverage',
                        $activeCount === 2 => 'Moderate Coverage',
                        default => 'Weak Coverage',
                    },
                ];
            })
            ->values();
    }

    public function distributions(Collection $opportunities, Collection $summaries): array
    {
        return [
            'By Opportunity Type' => $this->distribution($opportunities, fn (Opportunity $opportunity) => $opportunity->type ?: 'No type'),
            'By Status' => $this->distribution($opportunities, fn (Opportunity $opportunity) => $opportunity->status ?: 'No status'),
            'By Forecast Status' => $this->distribution($summaries, fn (array $summary) => $summary['forecast_status']),
            'By Readiness Status' => $this->distribution($summaries, fn (array $summary) => $this->readiness->statusForScore($summary['readiness_score'])),
            'By Focus Status' => $this->distribution($opportunities, fn (Opportunity $opportunity) => $opportunity->is_focus ? 'Focus' : 'Not Focus'),
        ];
    }

    public function themeAnalysis(): Collection
    {
        return Theme::query()
            ->with(['opportunities' => fn ($query) => $query->orderBy('title')])
            ->orderByDesc('active')
            ->orderByRaw('priority is null')
            ->orderBy('priority')
            ->orderBy('name')
            ->get()
            ->map(function (Theme $theme) {
                $opportunities = $theme->opportunities;
                $scores = $opportunities
                    ->map(fn (Opportunity $opportunity) => $opportunity->computedScore())
                    ->filter(fn ($score) => $score !== null);

                return [
                    'theme' => $theme,
                    'opportunity_count' => $opportunities->count(),
                    'focus_opportunity_count' => $opportunities->where('is_focus', true)->count(),
                    'won_count' => $opportunities->where('outcome', 'Won')->count(),
                    'lost_count' => $opportunities->where('outcome', 'Lost')->count(),
                    'abandoned_count' => $opportunities->where('outcome', 'Abandoned')->count(),
                    'average_score' => $this->average($scores),
                ];
            })
            ->values();
    }

    public function portfolioRisks(Collection $focusSummaries): Collection
    {
        return $focusSummaries
            ->map(function (array $summary) {
                $reasons = $this->riskReasons($summary);

                return array_merge($summary, ['risk_reasons' => $reasons]);
            })
            ->filter(fn (array $summary) => $summary['risk_reasons']->isNotEmpty())
            ->sortBy('forecast_score')
            ->values();
    }

    public function portfolioStrengths(Collection $focusSummaries): Collection
    {
        return $focusSummaries
            ->filter(fn (array $summary) => $summary['forecast_score'] >= 75
                && $summary['readiness_score'] >= 70
                && $summary['open_actions_count'] > 0
                && $summary['overdue_actions_count'] === 0)
            ->sortByDesc('forecast_score')
            ->values();
    }

    private function opportunities(): Collection
    {
        return Opportunity::query()
            ->with([
                'actions' => fn ($query) => $query->orderByRaw('due_date is null')->orderBy('due_date')->orderBy('id'),
                'opportunityGaps.actions',
                'projects',
                'strategicObjectives',
                'themes',
            ])
            ->latest()
            ->get();
    }

    private function summaries(Collection $opportunities, ?UserPreference $preference = null): Collection
    {
        return $opportunities
            ->map(fn (Opportunity $opportunity) => $this->summary($opportunity, $preference))
            ->values();
    }

    private function summary(Opportunity $opportunity, ?UserPreference $preference = null): array
    {
        $openActions = $this->openActions($opportunity);
        $overdueActions = $openActions
            ->filter(fn (Action $action) => $action->due_date !== null && $action->due_date->isBefore(today()))
            ->values();
        $criticalGaps = $this->criticalGaps($opportunity);

        return [
            'opportunity' => $opportunity,
            'weighted_score' => $this->forecast->weightedScore($opportunity, $preference),
            'readiness_score' => $this->readiness->score($opportunity),
            'forecast_score' => $this->forecast->score($opportunity, $preference),
            'forecast_status' => $this->forecast->status($opportunity, $preference),
            'open_actions_count' => $openActions->count(),
            'overdue_actions_count' => $overdueActions->count(),
            'critical_gaps_count' => $criticalGaps->count(),
            'strategic_objectives' => $opportunity->strategicObjectives,
            'missing_next_action' => $opportunity->missingNextAction(),
        ];
    }

    private function riskReasons(array $summary): Collection
    {
        return collect([
            $summary['forecast_score'] < 60 ? 'Forecast score below 60' : null,
            $summary['readiness_score'] < 50 ? 'Readiness score below 50' : null,
            $summary['overdue_actions_count'] > 0 ? $summary['overdue_actions_count'].' overdue action'.($summary['overdue_actions_count'] === 1 ? '' : 's') : null,
            $summary['critical_gaps_count'] > 0 ? $summary['critical_gaps_count'].' critical gap'.($summary['critical_gaps_count'] === 1 ? '' : 's') : null,
            $summary['missing_next_action'] ? 'Missing next action' : null,
        ])->filter()->values();
    }

    private function distribution(Collection $items, callable $labelResolver): Collection
    {
        return $items
            ->map(fn ($item) => $labelResolver($item))
            ->countBy()
            ->map(fn (int $count, string $label) => [
                'label' => $label,
                'count' => $count,
            ])
            ->sortByDesc('count')
            ->values();
    }

    private function average(Collection $scores): float
    {
        $scores = $scores->filter(fn ($score) => $score !== null);

        if ($scores->isEmpty()) {
            return 0.0;
        }

        return round($scores->avg(), 1);
    }

    private function isActive(Opportunity $opportunity): bool
    {
        return in_array($opportunity->status, Statuses::currentOpportunities(), true);
    }

    private function openActions(Opportunity $opportunity): Collection
    {
        return $opportunity->actions
            ->whereNull('completed_at')
            ->values();
    }

    private function criticalGaps(Opportunity $opportunity): Collection
    {
        return $opportunity->opportunityGaps
            ->filter(fn (OpportunityGap $gap) => $gap->status === Statuses::GAP_OPEN && $gap->priority === 'Critical')
            ->values();
    }
}
