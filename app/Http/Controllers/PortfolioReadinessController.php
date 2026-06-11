<?php

namespace App\Http\Controllers;

use App\Models\Opportunity;
use App\Models\UserPreference;
use App\Services\OpportunityReadinessService;
use App\Support\Statuses;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortfolioReadinessController extends Controller
{
    public function __invoke(Request $request, OpportunityReadinessService $readiness): View
    {
        $preference = $request->user()?->preference;
        $sort = $request->query('sort', 'weighted_score');
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $opportunities = Opportunity::query()
            ->whereNotIn('status', Statuses::terminalOpportunities())
            ->with(['opportunityGaps', 'projects', 'applications', 'strategicObjectives'])
            ->latest()
            ->get()
            ->map(fn (Opportunity $opportunity) => [
                'opportunity' => $opportunity,
                'weighted_score' => $this->rankedScore($opportunity, $preference),
                'readiness' => $readiness->indicators($opportunity),
            ]);

        $opportunities = $opportunities
            ->sortBy(fn (array $row) => $sort === 'readiness_score'
                ? $row['readiness']['score']
                : ($row['weighted_score'] ?? PHP_INT_MIN), SORT_REGULAR, $direction === 'desc')
            ->values();

        return view('portfolio-readiness.index', [
            'direction' => $direction,
            'opportunities' => $opportunities,
            'sort' => $sort,
        ]);
    }

    private function rankedScore(Opportunity $opportunity, ?UserPreference $preference = null): ?int
    {
        return $preference
            ? $opportunity->weightedScore($preference)
            : $opportunity->computedScore();
    }
}
