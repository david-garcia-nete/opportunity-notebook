<?php

namespace App\Services;

use App\Models\Action;
use App\Models\Contact;
use App\Models\ContactInteraction;
use App\Models\Opportunity;
use App\Models\OpportunityDecision;
use App\Models\OpportunityGap;
use App\Models\StrategicObjective;
use App\Models\Theme;
use App\Support\Statuses;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class OpportunityStrategicContextService
{
    public function __construct(
        private OpportunityForecastService $forecast,
        private OpportunityReadinessService $readiness,
        private OpportunityTimelineService $timeline,
    ) {
    }

    public function build(Opportunity $opportunity): array
    {
        $opportunity->load([
            'actions' => fn ($query) => $query->orderByRaw('due_date is null')->orderBy('due_date')->orderBy('id'),
            'applications' => fn ($query) => $query->latest('applied_at')->latest(),
            'contactInteractions' => fn ($query) => $query->with('contact')->latest('interaction_date')->latest(),
            'contacts' => fn ($query) => $query->orderBy('name'),
            'decisions' => fn ($query) => $query->with('review')->latest('decided_at')->latest(),
            'opportunityGaps' => fn ($query) => $query->with(['actions' => fn ($actionQuery) => $actionQuery->orderByRaw('completed_at is not null')->orderByRaw('due_date is null')->orderBy('due_date')->orderBy('id')])
                ->orderByRaw("case priority when 'Critical' then 1 when 'High' then 2 when 'Medium' then 3 else 4 end")
                ->orderBy('title'),
            'projects' => fn ($query) => $query->orderBy('name'),
            'strategicObjectives' => fn ($query) => $query->orderByDesc('priority')->orderBy('name'),
            'themes' => fn ($query) => $query->orderByRaw('priority is null')->orderBy('priority')->orderBy('name'),
        ]);

        $preference = auth()->user()?->preference;
        $readinessScore = $this->readiness->score($opportunity);
        $forecastScore = $this->forecast->score($opportunity, $preference);
        $timeline = $this->timeline->forOpportunity($opportunity);
        $openGaps = $opportunity->opportunityGaps->where('status', Statuses::GAP_OPEN)->values();
        $openActions = $opportunity->actions->whereNull('completed_at')->values();
        $completedActions = $opportunity->actions->whereNotNull('completed_at')
            ->sortByDesc(fn (Action $action) => $action->completed_at?->timestamp ?? 0)
            ->take(5)
            ->values();

        return [
            'identity' => [
                'id' => $opportunity->id,
                'title' => $opportunity->title,
                'company' => $opportunity->company,
                'status' => $opportunity->status,
                'type' => $opportunity->type,
                'notes' => $opportunity->notes,
                'created_at' => $this->dateTime($opportunity->created_at),
                'updated_at' => $this->dateTime($opportunity->updated_at),
            ],
            'scores' => [
                'manual_score' => $opportunity->score,
                'computed_score' => $opportunity->computedScore(),
                'weighted_score' => $this->forecast->weightedScore($opportunity, $preference),
                'forecast_score' => $forecastScore,
                'forecast_status' => $this->forecast->statusForScore($forecastScore),
                'readiness_score' => $readinessScore,
                'readiness_status' => $this->readiness->statusForScore($readinessScore),
            ],
            'focus' => [
                'is_focus' => $opportunity->is_focus,
                'state' => $opportunity->is_focus ? 'Focus' : 'Not Focus',
                'focused_at' => $this->dateTime($opportunity->focused_at),
                'reason' => $opportunity->focus_reason,
            ],
            'themes' => $opportunity->themes->map(fn (Theme $theme) => [
                'id' => $theme->id,
                'name' => $theme->name,
                'description' => $theme->description,
                'priority' => $theme->priority,
                'active' => $theme->active,
            ])->values()->all(),
            'strategic_objectives' => $opportunity->strategicObjectives->map(fn (StrategicObjective $objective) => [
                'id' => $objective->id,
                'name' => $objective->name,
                'description' => $objective->description,
                'priority' => $objective->priority,
                'active' => $objective->active,
            ])->values()->all(),
            'actions' => [
                'open' => $openActions->map(fn (Action $action) => $this->actionContext($action))->all(),
                'completed_recent' => $completedActions->map(fn (Action $action) => $this->actionContext($action))->all(),
            ],
            'gaps' => [
                'open' => $openGaps->map(fn (OpportunityGap $gap) => $this->gapContext($gap))->all(),
                'critical' => $openGaps->where('priority', 'Critical')->map(fn (OpportunityGap $gap) => $this->gapContext($gap))->values()->all(),
            ],
            'contacts' => $opportunity->contacts->map(fn (Contact $contact) => [
                'id' => $contact->id,
                'name' => $contact->name,
                'organization' => $contact->organization,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'relationship_type' => $contact->pivot?->relationship_type,
                'notes' => $contact->pivot?->notes,
            ])->values()->all(),
            'recent_contact_interactions' => $opportunity->contactInteractions
                ->take(5)
                ->map(fn (ContactInteraction $interaction) => $this->contactInteractionContext($interaction))
                ->all(),
            'decisions' => [
                'recent' => $opportunity->decisions
                    ->take(5)
                    ->map(fn (OpportunityDecision $decision) => $this->decisionContext($decision))
                    ->all(),
                'latest_review_linked' => $opportunity->decisions
                    ->filter(fn (OpportunityDecision $decision) => $decision->review_id !== null)
                    ->take(5)
                    ->map(fn (OpportunityDecision $decision) => $this->decisionContext($decision))
                    ->values()
                    ->all(),
            ],
            'outcome_learning' => [
                'outcome' => $opportunity->outcome,
                'outcome_date' => $this->date($opportunity->outcome_date),
                'outcome_reason' => $opportunity->outcome_reason,
                'outcome_reason_label' => $opportunity->outcomeReasonLabel(),
                'outcome_notes' => $opportunity->outcome_notes,
                'lesson_learned' => $opportunity->lesson_learned,
            ],
            'timeline_summary' => $this->timelineSummary($timeline),
        ];
    }

    private function actionContext(Action $action): array
    {
        return [
            'id' => $action->id,
            'title' => $action->title,
            'description' => $action->description,
            'status' => $action->status(),
            'due_date' => $this->date($action->due_date),
            'completed_at' => $this->dateTime($action->completed_at),
            'opportunity_gap_id' => $action->opportunity_gap_id,
            'opportunity_gap_title' => $action->opportunityGap?->title,
        ];
    }

    private function gapContext(OpportunityGap $gap): array
    {
        return [
            'id' => $gap->id,
            'title' => $gap->title,
            'description' => $gap->description,
            'category' => $gap->category,
            'status' => $gap->status,
            'priority' => $gap->priority,
            'open_actions_count' => $gap->actions->whereNull('completed_at')->count(),
            'completed_actions_count' => $gap->actions->whereNotNull('completed_at')->count(),
        ];
    }

    private function contactInteractionContext(ContactInteraction $interaction): array
    {
        return [
            'id' => $interaction->id,
            'contact_id' => $interaction->contact_id,
            'contact_name' => $interaction->contact?->name,
            'interaction_date' => $this->date($interaction->interaction_date),
            'interaction_type' => $interaction->interaction_type,
            'summary' => $interaction->summary,
            'outcome' => $interaction->outcome,
            'next_follow_up_date' => $this->date($interaction->next_follow_up_date),
        ];
    }

    private function decisionContext(OpportunityDecision $decision): array
    {
        return [
            'id' => $decision->id,
            'decision_type' => $decision->decision_type,
            'decision_type_label' => $decision->decisionTypeLabel(),
            'reason_category' => $decision->reason_category,
            'reason_category_label' => $decision->reasonCategoryLabel(),
            'notes' => $decision->notes,
            'decided_at' => $this->dateTime($decision->decided_at),
            'review_id' => $decision->review_id,
            'review_type' => $decision->review?->review_type,
        ];
    }

    private function timelineSummary(array $timeline): array
    {
        return [
            'upcoming_count' => $timeline['upcoming']->count(),
            'history_count' => $timeline['history']->count(),
            'next_upcoming' => $this->timelineItems($timeline['upcoming'], 3),
            'recent_history' => $this->timelineItems($timeline['history'], 5),
        ];
    }

    private function timelineItems(Collection $items, int $limit): array
    {
        return $items->take($limit)->map(fn (array $item) => [
            'date' => $this->date($item['date']),
            'type_label' => $item['type_label'],
            'title' => $item['title'],
            'status' => $item['status'],
            'contact' => $item['contact'],
        ])->values()->all();
    }

    private function date(Carbon|string|null $date): ?string
    {
        if ($date === null) {
            return null;
        }

        return ($date instanceof Carbon ? $date : Carbon::parse($date))->toDateString();
    }

    private function dateTime(Carbon|string|null $date): ?string
    {
        if ($date === null) {
            return null;
        }

        return ($date instanceof Carbon ? $date : Carbon::parse($date))->toDateTimeString();
    }
}
