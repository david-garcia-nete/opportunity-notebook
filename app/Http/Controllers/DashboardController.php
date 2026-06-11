<?php

namespace App\Http\Controllers;

use App\Models\Action;
use App\Models\Application;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\Project;
use App\Models\StrategicObjective;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $rankedOpportunities = $this->rankedOpportunities();

        $opportunityCount = Opportunity::count();
        $activeOpportunityCount = Opportunity::whereNotIn('status', ['rejected', 'closed'])->count();
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
            'topRankedOpportunities' => $rankedOpportunities->take(5),
            'highValueOpportunitiesMissingNextAction' => $rankedOpportunities
                ->filter(fn (Opportunity $opportunity) => $opportunity->missingNextAction())
                ->take(5)
                ->values(),
            'overdueActionsOnHighValueOpportunities' => $this->overdueActionsOnHighValueOpportunities(),
            'recentApplicationsForHighValueOpportunities' => $this->recentApplicationsForHighValueOpportunities(),
            'topObjectives' => $this->topObjectives(),
        ]);
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

    private function rankedOpportunities(): Collection
    {
        return Opportunity::query()
            ->whereNotIn('status', ['rejected', 'closed'])
            ->with(['actions' => fn ($query) => $query->orderByRaw('due_date is null')->orderBy('due_date')->orderBy('id')])
            ->latest()
            ->get()
            ->filter(fn (Opportunity $opportunity) => $opportunity->computedScore() !== null)
            ->sortByDesc(fn (Opportunity $opportunity) => $opportunity->computedScore())
            ->values();
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
