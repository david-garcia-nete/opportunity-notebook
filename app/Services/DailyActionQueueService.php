<?php

namespace App\Services;

use App\Models\Action;
use App\Models\ContactInteraction;
use App\Models\Opportunity;
use App\Models\OpportunityGap;
use App\Support\Statuses;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DailyActionQueueService
{
    public function __construct(private OpportunityReadinessService $readiness)
    {
    }

    public function build(): Collection
    {
        return collect()
            ->merge($this->overdueFocusActions())
            ->merge($this->dueTodayFocusActions())
            ->merge($this->focusOpportunitiesMissingNextAction())
            ->merge($this->dueContactFollowUps())
            ->merge($this->lowReadinessFocusOpportunities())
            ->merge($this->focusOpportunityGapsWithoutActionPlans())
            ->merge($this->focusOpportunityGaps('Critical', 6, 'Close this critical gap before investing in lower-priority work.'))
            ->merge($this->focusOpportunityGaps('High', 7, 'Make progress on this high-priority gap.'))
            ->sortBy([
                ['priority', 'asc'],
                ['due_date_sort', 'asc'],
                ['title', 'asc'],
            ])
            ->values()
            ->map(fn (array $item) => collect($item)->except('due_date_sort')->all());
    }

    public function summary(?Collection $queueItems = null): array
    {
        $queueItems ??= $this->build();

        return [
            'focus_opportunities_count' => Opportunity::where('is_focus', true)->count(),
            'queue_item_count' => $queueItems->count(),
            'overdue_action_count' => $this->focusActionQuery()
                ->whereDate('due_date', '<', today())
                ->count(),
            'due_today_action_count' => $this->focusActionQuery()
                ->whereDate('due_date', today())
                ->count(),
            'follow_ups_due_count' => ContactInteraction::query()
                ->whereNotNull('next_follow_up_date')
                ->whereDate('next_follow_up_date', '<=', today())
                ->count(),
            'critical_gap_count' => OpportunityGap::query()
                ->where('status', Statuses::GAP_OPEN)
                ->where('priority', 'Critical')
                ->whereHas('opportunity', fn ($query) => $query->where('is_focus', true))
                ->count(),
        ];
    }

    private function overdueFocusActions(): Collection
    {
        return $this->focusActionQuery()
            ->whereDate('due_date', '<', today())
            ->orderBy('due_date')
            ->orderBy('id')
            ->get()
            ->map(fn (Action $action) => $this->actionItem(
                action: $action,
                priority: 1,
                priorityLabel: 'Priority 1 · Overdue focus action',
                recommendedNextStep: 'Complete or reschedule this overdue action today.'
            ));
    }

    private function dueTodayFocusActions(): Collection
    {
        return $this->focusActionQuery()
            ->whereDate('due_date', today())
            ->orderBy('id')
            ->get()
            ->map(fn (Action $action) => $this->actionItem(
                action: $action,
                priority: 2,
                priorityLabel: 'Priority 2 · Due today focus action',
                recommendedNextStep: 'Complete this action before adding new work.'
            ));
    }

    private function focusOpportunitiesMissingNextAction(): Collection
    {
        return Opportunity::query()
            ->where('is_focus', true)
            ->with(['actions' => fn ($query) => $query->orderByRaw('due_date is null')->orderBy('due_date')->orderBy('id')])
            ->orderBy('title')
            ->get()
            ->filter(fn (Opportunity $opportunity) => $opportunity->missingNextAction())
            ->values()
            ->map(fn (Opportunity $opportunity) => [
                'type' => 'opportunity',
                'type_label' => 'Opportunity',
                'opportunity' => $opportunity,
                'title' => $opportunity->title.' has no next action',
                'due_date' => null,
                'due_date_sort' => today()->addYears(10),
                'priority' => 3,
                'priority_label' => 'Priority 3 · Missing next action',
                'recommended_next_step' => 'Create one concrete next action for this focus opportunity.',
                'url' => route('actions.create', ['opportunity_id' => $opportunity->id]),
            ]);
    }

    private function dueContactFollowUps(): Collection
    {
        return ContactInteraction::query()
            ->with(['contact', 'opportunity'])
            ->whereNotNull('next_follow_up_date')
            ->whereDate('next_follow_up_date', '<=', today())
            ->orderBy('next_follow_up_date')
            ->orderBy('id')
            ->get()
            ->map(fn (ContactInteraction $interaction) => [
                'type' => 'follow-up',
                'type_label' => 'Follow-up',
                'opportunity' => $interaction->opportunity,
                'title' => 'Follow up with '.$interaction->contact->name,
                'due_date' => $interaction->next_follow_up_date,
                'due_date_sort' => $interaction->next_follow_up_date,
                'priority' => 4,
                'priority_label' => 'Priority 4 · Contact follow-up due',
                'recommended_next_step' => 'Reach out to '.$interaction->contact->name.' and log the outcome.',
                'url' => route('contacts.show', $interaction->contact),
            ]);
    }

    private function lowReadinessFocusOpportunities(): Collection
    {
        return Opportunity::query()
            ->where('is_focus', true)
            ->with(['opportunityGaps', 'projects', 'applications', 'strategicObjectives'])
            ->orderBy('title')
            ->get()
            ->filter(fn (Opportunity $opportunity) => $this->readiness->score($opportunity) < 50)
            ->values()
            ->map(fn (Opportunity $opportunity) => [
                'type' => 'readiness',
                'type_label' => 'Readiness',
                'opportunity' => $opportunity,
                'title' => $opportunity->title.' opportunity is not yet ready for pursuit.',
                'due_date' => null,
                'due_date_sort' => today()->addYears(10),
                'priority' => 4.5,
                'priority_label' => 'Priority 5 · Low readiness focus opportunity',
                'recommended_next_step' => 'Review portfolio readiness and close the most important evidence gaps.',
                'url' => route('opportunities.show', $opportunity),
            ]);
    }

    private function focusOpportunityGapsWithoutActionPlans(): Collection
    {
        return OpportunityGap::query()
            ->with('opportunity')
            ->where('status', Statuses::GAP_OPEN)
            ->whereIn('priority', ['Critical', 'High'])
            ->whereDoesntHave('actions')
            ->whereHas('opportunity', fn ($query) => $query->where('is_focus', true))
            ->orderByRaw("case priority when 'Critical' then 1 else 2 end")
            ->orderBy('title')
            ->get()
            ->map(fn (OpportunityGap $gap) => [
                'type' => 'gap-action-plan',
                'type_label' => 'Gap',
                'opportunity' => $gap->opportunity,
                'title' => 'Gap has no action plan: '.$gap->title,
                'due_date' => null,
                'due_date_sort' => today()->addYears(10),
                'priority' => 5,
                'priority_label' => 'Priority 5 · Gap has no action plan',
                'recommended_next_step' => 'Create one action that starts closing this '.$gap->priority.' gap.',
                'url' => route('actions.create', ['opportunity_gap_id' => $gap->id]),
            ]);
    }

    private function focusOpportunityGaps(string $gapPriority, int $queuePriority, string $recommendedNextStep): Collection
    {
        return OpportunityGap::query()
            ->with('opportunity')
            ->where('status', Statuses::GAP_OPEN)
            ->where('priority', $gapPriority)
            ->whereHas('actions')
            ->whereHas('opportunity', fn ($query) => $query->where('is_focus', true))
            ->orderBy('title')
            ->get()
            ->map(fn (OpportunityGap $gap) => [
                'type' => 'gap',
                'type_label' => 'Gap',
                'opportunity' => $gap->opportunity,
                'title' => $gap->title.' remains open',
                'due_date' => null,
                'due_date_sort' => today()->addYears(10),
                'priority' => $queuePriority,
                'priority_label' => 'Priority '.$queuePriority.' · '.$gapPriority.' focus gap',
                'recommended_next_step' => $recommendedNextStep,
                'url' => route('opportunities.show', $gap->opportunity),
            ]);
    }

    private function focusActionQuery(): Builder
    {
        return Action::query()
            ->with('opportunity')
            ->whereNull('completed_at')
            ->whereNotNull('due_date')
            ->whereHas('opportunity', fn ($query) => $query->where('is_focus', true));
    }

    private function actionItem(Action $action, int $priority, string $priorityLabel, string $recommendedNextStep): array
    {
        return [
            'type' => 'action',
            'type_label' => 'Action',
            'opportunity' => $action->opportunity,
            'title' => $action->title,
            'due_date' => $action->due_date,
            'due_date_sort' => $action->due_date,
            'priority' => $priority,
            'priority_label' => $priorityLabel,
            'recommended_next_step' => $recommendedNextStep,
            'url' => route('actions.show', $action),
        ];
    }
}
