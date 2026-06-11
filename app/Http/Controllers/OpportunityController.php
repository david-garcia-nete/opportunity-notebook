<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OpportunityController extends Controller
{
    public function index(): View
    {
        $opportunities = Opportunity::query()
            ->with(['actions' => fn ($query) => $query->orderByRaw('due_date is null')->orderBy('due_date')->orderBy('id')])
            ->latest()
            ->get()
            ->sortByDesc(fn (Opportunity $opportunity) => $opportunity->computedScore() ?? PHP_INT_MIN)
            ->values();

        return view('opportunities.index', [
            'opportunities' => $opportunities,
        ]);
    }

    public function compare(): View
    {
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
            ->sortByDesc(fn (Opportunity $opportunity) => $opportunity->computedScore() ?? PHP_INT_MIN)
            ->values();

        return view('opportunities.compare', [
            'opportunities' => $opportunities,
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
        return view('opportunities.show', [
            'availableContacts' => Contact::orderBy('name')->get(),
            'availableProjects' => Project::orderBy('name')->get(),
            'opportunity' => $opportunity->load([
                'actions' => fn ($query) => $query->orderByRaw('due_date is null')->orderBy('due_date')->orderBy('id'),
                'applications' => fn ($query) => $query->latest('applied_at')->latest(),
                'contacts' => fn ($query) => $query->orderBy('name'),
                'projects' => fn ($query) => $query->orderBy('name'),
            ]),
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
        $opportunity->update($this->validatedOpportunity($request));

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

    private function validatedOpportunity(Request $request): array
    {
        return $request->validate([
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
            'notes' => ['nullable', 'string'],
        ]);
    }
}
