<?php

namespace App\Http\Controllers;

use App\Models\Action;
use App\Models\Application;
use App\Models\Contact;
use App\Models\ContactInteraction;
use App\Models\Opportunity;
use App\Models\OpportunityGap;
use App\Models\Project;
use App\Models\StrategicObjective;
use App\Models\UserPreference;
use App\Services\DailyActionQueueService;
use App\Services\OpportunityTimelineService;
use App\Support\Statuses;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(DailyActionQueueService $dailyActionQueue, OpportunityTimelineService $timeline): View
    {
        $preference = request()->user()?->preference;
        $rankedOpportunities = $this->rankedOpportunities($preference);
        $dailyQueueItems = $dailyActionQueue->build();

        $opportunityCount = Opportunity::count();
        $activeOpportunityCount = Opportunity::whereNotIn('status', Statuses::terminalOpportunities())->count();
        $actionCount = Action::count();
        $contactCount = Contact::count();
        $applicationCount = Application::count();
        $projectCount = Project::count();
        $applicationsThisWeekCount = Application::where('applied_at', '>=', now()->subDays(7))->count();
        $actionsDueTodayCount = Action::whereDate('due_date', today())
            ->whereNull('completed_at')
            ->count();
        $overdueActionCount = Action::whereDate('due_date', '<', today())
            ->whereNull('completed_at')
            ->count();

        $currentFocusOpportunities = $this->currentFocusOpportunities($preference);

        return view('dashboard', [
            'opportunityCount' => $opportunityCount,
            'activeOpportunityCount' => $activeOpportunityCount,
            'actionCount' => $actionCount,
            'contactCount' => $contactCount,
            'applicationCount' => $applicationCount,
            'projectCount' => $projectCount,
            'applicationsThisWeekCount' => $applicationsThisWeekCount,
            'actionsDueTodayCount' => $actionsDueTodayCount,
            'overdueActionCount' => $overdueActionCount,
            'preference' => $preference,
            'dailyQueueItems' => $dailyQueueItems,
            'dailyQueueSummary' => $dailyActionQueue->summary($dailyQueueItems),
            'currentFocusOpportunities' => $currentFocusOpportunities,
            'hasTooManyFocusOpportunities' => $currentFocusOpportunities->count() > 5,
            'topRankedOpportunities' => $rankedOpportunities->take(5),
            'highValueOpportunitiesMissingNextAction' => $rankedOpportunities
                ->filter(fn (Opportunity $opportunity) => $opportunity->missingNextAction())
                ->take(5)
                ->values(),
            'highValueOpportunitiesWithCriticalGaps' => $this->highValueOpportunitiesWithCriticalGaps($rankedOpportunities),
            'gapsWithoutActionPlans' => $this->gapsWithoutActionPlans(),
            'overdueActionsOnHighValueOpportunities' => $this->overdueActionsOnHighValueOpportunities(),
            'recentApplicationsForHighValueOpportunities' => $this->recentApplicationsForHighValueOpportunities(),
            'contactsRequiringFollowUp' => $this->contactsRequiringFollowUp(),
            'dormantHighValueRelationships' => $this->dormantHighValueRelationships(),
            'topObjectives' => $this->topObjectives(),
            'recentActivityItems' => $timeline->recentHistory(),
        ]);
    }


    private function currentFocusOpportunities(?UserPreference $preference = null): Collection
    {
        return Opportunity::query()
            ->where('is_focus', true)
            ->with(['actions' => fn ($query) => $query->orderByRaw('due_date is null')->orderBy('due_date')->orderBy('id')])
            ->latest('focused_at')
            ->latest()
            ->get()
            ->sortByDesc(fn (Opportunity $opportunity) => $this->rankedScore($opportunity, $preference) ?? PHP_INT_MIN)
            ->values();
    }

    private function contactsRequiringFollowUp(): Collection
    {
        return ContactInteraction::query()
            ->with(['contact', 'opportunity'])
            ->whereNotNull('next_follow_up_date')
            ->whereDate('next_follow_up_date', '<=', today())
            ->orderBy('next_follow_up_date')
            ->get()
            ->take(5)
            ->values();
    }

    private function dormantHighValueRelationships(): Collection
    {
        return Contact::query()
            ->with([
                'contactInteractions' => fn ($query) => $query->latest('interaction_date')->latest(),
                'opportunities',
            ])
            ->orderBy('name')
            ->get()
            ->map(function (Contact $contact) {
                $highValueOpportunities = $contact->opportunities
                    ->filter(fn (Opportunity $opportunity) => ($opportunity->computedScore() ?? PHP_INT_MIN) >= 30)
                    ->sortByDesc(fn (Opportunity $opportunity) => $opportunity->computedScore())
                    ->values();
                $lastInteraction = $contact->contactInteractions->first();

                return [
                    'contact' => $contact,
                    'high_value_opportunities' => $highValueOpportunities,
                    'last_interaction' => $lastInteraction,
                    'average_opportunity_score' => $contact->averageOpportunityScore(),
                ];
            })
            ->filter(function (array $summary) {
                if ($summary['high_value_opportunities']->isEmpty()) {
                    return false;
                }

                $lastInteraction = $summary['last_interaction'];

                return $lastInteraction === null
                    || $lastInteraction->interaction_date->lte(today()->subDays(30));
            })
            ->sortByDesc(fn (array $summary) => $summary['average_opportunity_score'] ?? PHP_INT_MIN)
            ->take(5)
            ->values();
    }

    private function topObjectives(): Collection
    {
        return StrategicObjective::query()
            ->where('active', true)
            ->with('opportunities')
            ->orderByDesc('priority')
            ->orderBy('name')
            ->get()
            ->map(function (StrategicObjective $objective) {
                $scoredOpportunities = $objective->opportunities
                    ->filter(fn (Opportunity $opportunity) => $opportunity->computedScore() !== null);

                return [
                    'objective' => $objective,
                    'linked_opportunity_count' => $objective->opportunities->count(),
                    'highest_ranked_opportunity' => $scoredOpportunities
                        ->sortByDesc(fn (Opportunity $opportunity) => $opportunity->computedScore())
                        ->first(),
                    'average_opportunity_score' => $scoredOpportunities->isEmpty()
                        ? null
                        : round($scoredOpportunities->avg(fn (Opportunity $opportunity) => $opportunity->computedScore()), 1),
                ];
            })
            ->take(5)
            ->values();
    }

    private function rankedOpportunities(?UserPreference $preference = null): Collection
    {
        return Opportunity::query()
            ->whereNotIn('status', Statuses::terminalOpportunities())
            ->with([
                'actions' => fn ($query) => $query->orderByRaw('due_date is null')->orderBy('due_date')->orderBy('id'),
                'opportunityGaps' => fn ($query) => $query->orderByRaw("case priority when 'Critical' then 1 when 'High' then 2 when 'Medium' then 3 else 4 end")->orderBy('title'),
            ])
            ->latest()
            ->get()
            ->filter(fn (Opportunity $opportunity) => $this->rankedScore($opportunity, $preference) !== null)
            ->sortByDesc(fn (Opportunity $opportunity) => $this->rankedScore($opportunity, $preference))
            ->values();
    }

    private function rankedScore(Opportunity $opportunity, ?UserPreference $preference = null): ?int
    {
        return $preference
            ? $opportunity->weightedScore($preference)
            : $opportunity->computedScore();
    }

    private function highValueOpportunitiesWithCriticalGaps(Collection $rankedOpportunities): Collection
    {
        return $rankedOpportunities
            ->map(function (Opportunity $opportunity) {
                $openGaps = $opportunity->opportunityGaps->where('status', Statuses::GAP_OPEN);

                return [
                    'opportunity' => $opportunity,
                    'open_gap_count' => $openGaps->count(),
                    'highest_priority_gap' => $openGaps
                        ->sortBy(fn ($gap) => $gap->priorityRank())
                        ->first(),
                ];
            })
            ->filter(fn (array $summary) => $summary['open_gap_count'] > 0)
            ->take(5)
            ->values();
    }

    private function gapsWithoutActionPlans(): Collection
    {
        return OpportunityGap::query()
            ->with('opportunity')
            ->where('status', Statuses::GAP_OPEN)
            ->whereIn('priority', ['Critical', 'High'])
            ->whereDoesntHave('actions')
            ->orderByRaw("case priority when 'Critical' then 1 else 2 end")
            ->orderBy('title')
            ->take(5)
            ->get();
    }

    private function overdueActionsOnHighValueOpportunities(): Collection
    {
        return Action::query()
            ->with('opportunity')
            ->whereNull('completed_at')
            ->whereDate('due_date', '<', today())
            ->get()
            ->filter(fn (Action $action) => $action->opportunity?->computedScore() !== null)
            ->sort(function (Action $first, Action $second) {
                $scoreComparison = $second->opportunity->computedScore() <=> $first->opportunity->computedScore();

                if ($scoreComparison !== 0) {
                    return $scoreComparison;
                }

                return $first->due_date->getTimestamp() <=> $second->due_date->getTimestamp();
            })
            ->take(5)
            ->values();
    }

    private function recentApplicationsForHighValueOpportunities(): Collection
    {
        return Application::query()
            ->with('opportunity')
            ->latest('applied_at')
            ->get()
            ->filter(fn (Application $application) => $application->opportunity?->computedScore() !== null)
            ->sort(function (Application $first, Application $second) {
                $scoreComparison = $second->opportunity->computedScore() <=> $first->opportunity->computedScore();

                if ($scoreComparison !== 0) {
                    return $scoreComparison;
                }

                return $second->applied_at->getTimestamp() <=> $first->applied_at->getTimestamp();
            })
            ->take(5)
            ->values();
    }
}
