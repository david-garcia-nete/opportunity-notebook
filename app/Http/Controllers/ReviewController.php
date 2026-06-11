<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompleteFocusReviewRequest;
use App\Models\Action;
use App\Models\Opportunity;
use App\Models\OpportunityDecision;
use App\Models\Review;
use App\Services\OpportunityForecastService;
use App\Services\OpportunityReadinessService;
use App\Services\OpportunityTimelineService;
use App\Support\Statuses;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(): View
    {
        return view('reviews.index', [
            'reviews' => Review::query()
                ->withCount('opportunityDecisions')
                ->orderByRaw('completed_at is null')
                ->latest('completed_at')
                ->latest()
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('reviews.create', [
            'reviewTypes' => Review::reviewTypeOptions(),
            'defaultCompletedAt' => now()->format('Y-m-d\\TH:i'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'review_type' => ['required', 'string', Rule::in(Review::REVIEW_TYPES)],
            'summary' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'completed_at' => ['nullable', 'date'],
        ]);

        if ($validated['review_type'] === 'focus') {
            $review = Review::create([
                'review_type' => 'focus',
                'started_at' => now(),
                'summary' => $validated['summary'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            return redirect()
                ->route('reviews.focus.show', $review)
                ->with('status', 'Focus review started.');
        }

        $validated['completed_at'] ??= now();

        $review = Review::create($validated);

        return redirect()
            ->route('reviews.show', $review)
            ->with('status', 'Review recorded.');
    }

    public function startFocus(): RedirectResponse
    {
        $review = Review::create([
            'review_type' => 'focus',
            'started_at' => now(),
        ]);

        return redirect()
            ->route('reviews.focus.show', $review)
            ->with('status', 'Focus review started.');
    }

    public function focus(Review $review, OpportunityForecastService $forecast, OpportunityReadinessService $readiness, OpportunityTimelineService $timeline): View|RedirectResponse
    {
        if ($review->review_type !== 'focus') {
            abort(404);
        }

        if ($review->completed_at !== null) {
            return redirect()->route('reviews.show', $review);
        }

        $opportunities = $this->focusOpportunities();

        return view('reviews.focus', [
            'review' => $review,
            'opportunities' => $opportunities,
            'decisionTypes' => OpportunityDecision::focusReviewDecisionTypeOptions(),
            'reasonCategories' => OpportunityDecision::reasonCategoryOptions(),
            'signals' => $this->focusSignals($opportunities, $forecast, $readiness, $timeline),
        ]);
    }

    public function completeFocus(CompleteFocusReviewRequest $request, Review $review): RedirectResponse
    {
        if ($review->review_type !== 'focus') {
            abort(404);
        }

        $validated = $request->validated();
        $reviewedOpportunityIds = collect($validated['decisions'] ?? [])->keys()->map(fn (string|int $id) => (int) $id);
        $focusOpportunityIds = $this->focusOpportunities()->pluck('id');

        abort_if($reviewedOpportunityIds->diff($focusOpportunityIds)->isNotEmpty(), 404);

        if ($focusOpportunityIds->diff($reviewedOpportunityIds)->isNotEmpty()) {
            return back()
                ->withErrors(['decisions' => 'Record a decision for each current focus opportunity.'])
                ->withInput();
        }

        foreach ($validated['decisions'] as $opportunityId => $decision) {
            $opportunity = Opportunity::findOrFail($opportunityId);

            $opportunity->decisions()->create([
                'review_id' => $review->id,
                'decision_type' => $decision['decision_type'],
                'reason_category' => $decision['reason_category'],
                'notes' => $decision['notes'] ?? null,
                'decided_at' => now(),
            ]);

            $nextAction = $validated['next_actions'][$opportunityId] ?? null;

            if (($nextAction['title'] ?? null) !== null) {
                Action::create([
                    'opportunity_id' => $opportunity->id,
                    'title' => $nextAction['title'],
                    'due_date' => $nextAction['due_date'] ?? null,
                    'description' => $nextAction['description'] ?? null,
                ]);
            }
        }

        $review->update([
            'completed_at' => now(),
        ]);

        return redirect()
            ->route('reviews.show', $review)
            ->with('status', 'Focus review completed.');
    }

    public function show(Review $review): View
    {
        return view('reviews.show', [
            'review' => $review->load([
                'opportunityDecisions' => fn ($query) => $query->with('opportunity')->latest('decided_at')->latest(),
            ]),
        ]);
    }

    private function focusOpportunities(): Collection
    {
        return Opportunity::query()
            ->where('is_focus', true)
            ->whereIn('status', Statuses::currentOpportunities())
            ->with([
                'actions',
                'applications',
                'contactInteractions.contact',
                'contacts',
                'opportunityGaps.actions',
                'projects',
                'strategicObjectives',
                'decisions',
            ])
            ->orderByRaw('focused_at is null')
            ->latest('focused_at')
            ->orderBy('title')
            ->get();
    }

    private function focusSignals(Collection $opportunities, OpportunityForecastService $forecast, OpportunityReadinessService $readiness, OpportunityTimelineService $timeline): array
    {
        return $opportunities
            ->mapWithKeys(function (Opportunity $opportunity) use ($forecast, $readiness, $timeline) {
                $readinessScore = $readiness->score($opportunity);
                $opportunityTimeline = $timeline->forOpportunity($opportunity);

                return [$opportunity->id => [
                    'computed_score' => $opportunity->computedScore(),
                    'weighted_score' => $forecast->weightedScore($opportunity, auth()->user()?->preference),
                    'forecast_score' => $forecast->score($opportunity, auth()->user()?->preference),
                    'forecast_status' => $forecast->status($opportunity, auth()->user()?->preference),
                    'readiness_score' => $readinessScore,
                    'readiness_status' => $readiness->statusForScore($readinessScore),
                    'open_gaps' => $opportunity->opportunityGaps->where('status', Statuses::GAP_OPEN)->sortBy(fn ($gap) => $gap->priorityRank())->values(),
                    'overdue_actions' => $opportunity->actions
                        ->filter(fn (Action $action) => $action->completed_at === null && $action->due_date !== null && $action->due_date->isBefore(today()))
                        ->sortBy('due_date')
                        ->values(),
                    'missing_next_action' => $opportunity->missingNextAction(),
                    'recent_activity' => $opportunityTimeline['history']->take(3)->values(),
                    'follow_ups' => $opportunity->contactInteractions
                        ->filter(fn ($interaction) => $interaction->next_follow_up_date !== null && $interaction->next_follow_up_date->greaterThanOrEqualTo(today()))
                        ->sortBy('next_follow_up_date')
                        ->take(3)
                        ->values(),
                ]];
            })
            ->all();
    }
}
