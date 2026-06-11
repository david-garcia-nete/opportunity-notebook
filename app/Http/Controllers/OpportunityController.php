<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\OpportunityGap;
use App\Models\Project;
use App\Models\StrategicObjective;
use App\Models\UserPreference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->whereNotIn('status', ['rejected', 'closed'])
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
        return view('opportunities.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $opportunity = Opportunity::create($this->validatedOpportunity($request));

        return redirect()
            ->route('opportunities.show', $opportunity)
            ->with('status', 'Opportunity created.');
    }

    public function show(Opportunity $opportunity): View
    {
        $opportunity->load([
            'actions' => fn ($query) => $query->orderByRaw('due_date is null')->orderBy('due_date')->orderBy('id'),
            'applications' => fn ($query) => $query->latest('applied_at')->latest(),
            'contactInteractions' => fn ($query) => $query->with('contact')->latest('interaction_date')->latest(),
            'contacts' => fn ($query) => $query->orderBy('name'),
            'opportunityGaps' => fn ($query) => $query->with(['actions' => fn ($actionQuery) => $actionQuery->orderByRaw('completed_at is not null')->orderByRaw('due_date is null')->orderBy('due_date')->orderBy('id')])->orderByRaw("case priority when 'Critical' then 1 when 'High' then 2 when 'Medium' then 3 else 4 end")->orderBy('title'),
            'projects' => fn ($query) => $query->orderBy('name'),
            'strategicObjectives' => fn ($query) => $query->orderByDesc('priority')->orderBy('name'),
        ]);

        $gapCounts = collect(OpportunityGap::STATUSES)
            ->mapWithKeys(fn (string $status) => [$status => $opportunity->opportunityGaps->where('status', $status)->count()]);
        $gapPriorityCounts = collect(['Critical', 'High'])
            ->mapWithKeys(fn (string $priority) => [$priority => $opportunity->opportunityGaps->where('status', 'Open')->where('priority', $priority)->count()]);
        $gapActions = $opportunity->opportunityGaps->flatMap->actions;

        return view('opportunities.show', [
            'availableContacts' => Contact::orderBy('name')->get(),
            'availableProjects' => Project::orderBy('name')->get(),
            'availableStrategicObjectives' => StrategicObjective::orderByDesc('active')->orderByDesc('priority')->orderBy('name')->get(),
            'gapActionCompletedCount' => $gapActions->whereNotNull('completed_at')->count(),
            'gapActionOpenCount' => $gapActions->whereNull('completed_at')->count(),
            'gapCounts' => $gapCounts,
            'gapPriorityCounts' => $gapPriorityCounts,
            'gapStatuses' => OpportunityGap::STATUSES,
            'opportunity' => $opportunity,
        ]);
    }

    public function edit(Opportunity $opportunity): View
    {
        return view('opportunities.edit', [
            'opportunity' => $opportunity,
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
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:255'],
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
            'notes' => ['nullable', 'string'],
        ]);

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
