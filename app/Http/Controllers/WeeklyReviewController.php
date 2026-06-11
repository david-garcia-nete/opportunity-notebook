<?php

namespace App\Http\Controllers;

use App\Models\Action;
use App\Models\ContactInteraction;
use App\Models\Opportunity;
use App\Models\OpportunityGap;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class WeeklyReviewController extends Controller
{
    public function __invoke(): View
    {
        return view('weekly-review', [
            'currentFocusOpportunities' => $this->currentFocusOpportunities(),
            'completedActionsThisWeek' => $this->completedActionsThisWeek(),
            'overdueActions' => $this->overdueActions(),
            'openHighPriorityGaps' => $this->openHighPriorityGaps(),
            'contactFollowUpsDue' => $this->contactFollowUpsDue(),
        ]);
    }

    private function currentFocusOpportunities(): Collection
    {
        return Opportunity::query()
            ->where('is_focus', true)
            ->with([
                'actions' => fn ($query) => $query
                    ->orderByRaw('due_date is null')
                    ->orderBy('due_date')
                    ->orderBy('id'),
            ])
            ->withCount([
                'opportunityGaps as open_gaps_count' => fn ($query) => $query->where('status', '!=', 'Complete'),
                'actions as overdue_actions_count' => fn ($query) => $query
                    ->whereNull('completed_at')
                    ->whereDate('due_date', '<', today()),
            ])
            ->latest('focused_at')
            ->latest()
            ->get();
    }

    private function completedActionsThisWeek(): Collection
    {
        return Action::query()
            ->with('opportunity')
            ->whereBetween('completed_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->orderByDesc('completed_at')
            ->get();
    }

    private function overdueActions(): Collection
    {
        return Action::query()
            ->with('opportunity')
            ->whereNull('completed_at')
            ->whereDate('due_date', '<', today())
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();
    }

    private function openHighPriorityGaps(): Collection
    {
        return OpportunityGap::query()
            ->with('opportunity')
            ->where('status', '!=', 'Complete')
            ->whereIn('priority', ['Critical', 'High'])
            ->orderByRaw("case priority when 'Critical' then 0 when 'High' then 1 else 2 end")
            ->orderBy('title')
            ->get();
    }

    private function contactFollowUpsDue(): Collection
    {
        return ContactInteraction::query()
            ->with(['contact', 'opportunity'])
            ->whereNotNull('next_follow_up_date')
            ->whereDate('next_follow_up_date', '<=', today())
            ->orderBy('next_follow_up_date')
            ->orderBy('id')
            ->get();
    }
}
