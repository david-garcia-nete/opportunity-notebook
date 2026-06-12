<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\OpportunityDecision;
use App\Models\OpportunityGap;
use App\Models\Project;
use App\Models\StrategicObjective;
use App\Models\UserPreference;
use App\Services\OpportunityReadinessService;
use App\Services\OpportunityForecastService;
use App\Services\OpportunityTimelineService;
use App\Support\Statuses;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OpportunityController extends Controller
{
    public function index(Request $request): View
    {
        $focusFilter = $request->boolean('focus');

        $opportunities = Opportunity::query()
            ->when($focusFilter, fn ($query) => $query->where('is_focus', true))
            ->with(['actions' => fn ($query) => $query->orderByRaw('due_date is null')->orderBy('due_date')->orderBy('id')])
            ->latest()
            ->get()
            ->sortByDesc(fn (Opportunity $opportunity) => $opportunity->computedScore() ?? PHP_INT_MIN)
            ->values();

        return view('opportunities.index', [
            'focusFilter' => $focusFilter,
            'opportunities' => $opportunities,
        ]);
    }

    public function compare(): View
    {
        $preference = request()->user()?->preference;

        $opportunities = Opportunity::query()
            ->whereNotIn('status', Statuses::terminalOpportunities())
            ->withCount([
                'contacts',
                'projects',
                'applications',
                'actions as open_actions_count' => fn ($query) => $query->whereNull('completed_at'),
            ])
            ->latest()
            ->get()
            ->sortByDesc(fn (Opportunity $opportunity) => $this->rankedScore($opportunity, $preference))
            ->values();

        return view('opportunities.compare', [
            'opportunities' => $opportunities,
            'preference' => $preference,
        ]);
    }

    public function create(): View
    {
        return view('opportunities.create', [
            'defaultStatus' => Statuses::OPPORTUNITY_IDEA,
            'statuses' => Statuses::opportunities(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $opportunity = Opportunity::create($this->validatedOpportunity($request));

        return redirect()
            ->route('opportunities.show', $opportunity)
            ->with('status', 'Opportunity created.');
    }

    public function show(Opportunity $opportunity, OpportunityTimelineService $timeline, OpportunityReadinessService $readiness, OpportunityForecastService $forecast): View
    {
        $opportunity->load([
            'actions' => fn ($query) => $query->orderByRaw('due_date is null')->orderBy('due_date')->orderBy('id'),
            'applications' => fn ($query) => $query->latest('applied_at')->latest(),
            'contactInteractions' => fn ($query) => $query->with('contact')->latest('interaction_date')->latest(),
            'contacts' => fn ($query) => $query->orderBy('name'),
            'decisions' => fn ($query) => $query->latest('decided_at')->latest(),
            'opportunityGaps' => fn ($query) => $query->with(['actions' => fn ($actionQuery) => $actionQuery->orderByRaw('completed_at is not null')->orderByRaw('due_date is null')->orderBy('due_date')->orderBy('id')])->orderByRaw("case priority when 'Critical' then 1 when 'High' then 2 when 'Medium' then 3 else 4 end")->orderBy('title'),
            'projects' => fn ($query) => $query->orderBy('name'),
            'strategicObjectives' => fn ($query) => $query->orderByDesc('priority')->orderBy('name'),
        ]);

        $gapCounts = collect(Statuses::gaps())
            ->mapWithKeys(fn (string $status) => [$status => $opportunity->opportunityGaps->where('status', $status)->count()]);
        $gapPriorityCounts = collect(['Critical', 'High'])
            ->mapWithKeys(fn (string $priority) => [$priority => $opportunity->opportunityGaps->where('status', Statuses::GAP_OPEN)->where('priority', $priority)->count()]);
        $gapActions = $opportunity->opportunityGaps->flatMap->actions;

        return view('opportunities.show', [
            'availableContacts' => Contact::orderBy('name')->get(),
            'availableProjects' => Project::orderBy('name')->get(),
            'availableStrategicObjectives' => StrategicObjective::orderByDesc('active')->orderByDesc('priority')->orderBy('name')->get(),
            'gapActionCompletedCount' => $gapActions->whereNotNull('completed_at')->count(),
            'gapActionOpenCount' => $gapActions->whereNull('completed_at')->count(),
            'gapCounts' => $gapCounts,
            'gapPriorityCounts' => $gapPriorityCounts,
            'gapStatuses' => Statuses::gaps(),
            'opportunity' => $opportunity,
            'forecastBreakdown' => $forecast->breakdown($opportunity, request()->user()?->preference),
            'forecastScore' => $forecast->score($opportunity, request()->user()?->preference),
            'forecastStatus' => $forecast->status($opportunity, request()->user()?->preference),
            'decisionTypes' => OpportunityDecision::decisionTypeOptions(),
            'reasonCategories' => OpportunityDecision::reasonCategoryOptions(),
            'readinessBreakdown' => $readiness->breakdown($opportunity),
            'readinessIndicators' => $readiness->indicators($opportunity),
            'timeline' => $timeline->forOpportunity($opportunity),
        ]);
    }

    public function edit(Opportunity $opportunity): View
    {
        return view('opportunities.edit', [
            'opportunity' => $opportunity,
            'outcomeReasons' => Opportunity::outcomeReasonOptions(),
            'outcomes' => Opportunity::OUTCOMES,
            'statuses' => Statuses::opportunities(),
        ]);
    }

    public function update(Request $request, Opportunity $opportunity): RedirectResponse
    {
        $opportunity->update($this->validatedOpportunity($request, $opportunity));

        return redirect()
            ->route('opportunities.show', $opportunity)
            ->with('status', 'Opportunity updated.');
    }

    public function destroy(Opportunity $opportunity): RedirectResponse
    {
        $opportunity->delete();

        return redirect()
            ->route('opportunities.index')
            ->with('status', 'Opportunity deleted.');
    }

    private function rankedScore(Opportunity $opportunity, ?UserPreference $preference = null): int
    {
        return $preference
            ? $opportunity->weightedScore($preference) ?? PHP_INT_MIN
            : $opportunity->computedScore() ?? PHP_INT_MIN;
    }

    private function validatedOpportunity(Request $request, ?Opportunity $opportunity = null): array
    {
        if ($normalizedStatus = Statuses::normalizeOpportunity($request->input('status'))) {
            $request->merge(['status' => $normalizedStatus]);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::in(Statuses::opportunities())],
            'score' => ['nullable', 'integer', 'min:0'],
            'income_potential' => ['nullable', 'integer', 'min:1', 'max:10'],
            'probability_of_success' => ['nullable', 'integer', 'min:1', 'max:10'],
            'time_to_revenue' => ['nullable', 'integer', 'min:1', 'max:10'],
            'strategic_alignment' => ['nullable', 'integer', 'min:1', 'max:10'],
            'personal_interest' => ['nullable', 'integer', 'min:1', 'max:10'],
            'skill_growth' => ['nullable', 'integer', 'min:1', 'max:10'],
            'family_fit' => ['nullable', 'integer', 'min:1', 'max:10'],
            'risk_level' => ['nullable', 'integer', 'min:1', 'max:10'],
            'is_focus' => ['nullable', 'boolean'],
            'focus_reason' => ['nullable', 'string'],
            'outcome' => ['nullable', 'string', Rule::in(Opportunity::OUTCOMES)],
            'outcome_date' => ['nullable', 'date', 'required_with:outcome'],
            'outcome_reason' => ['nullable', 'string', Rule::in(array_keys(Opportunity::outcomeReasonOptions()))],
            'outcome_notes' => ['nullable', 'string'],
            'lesson_learned' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        if (($validated['outcome_reason'] ?? null) && ! array_key_exists($validated['outcome_reason'], Opportunity::outcomeReasonOptionsFor($validated['outcome'] ?? null))) {
            throw ValidationException::withMessages([
                'outcome_reason' => 'The selected outcome reason is not valid for this outcome.',
            ]);
        }

        if (! in_array($validated['outcome'] ?? null, Opportunity::OUTCOMES_WITH_LEARNING, true)) {
            $validated['outcome_reason'] = null;
            $validated['lesson_learned'] = null;
        }

        $validated['is_focus'] = $request->boolean('is_focus');

        if ($validated['is_focus']) {
            $validated['focused_at'] = $opportunity?->focused_at ?? now();
        } else {
            $validated['focused_at'] = null;
            $validated['focus_reason'] = null;
        }

        return $validated;
    }
}
