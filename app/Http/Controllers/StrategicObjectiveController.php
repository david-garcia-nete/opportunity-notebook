<?php

namespace App\Http\Controllers;

use App\Models\Opportunity;
use App\Models\StrategicObjective;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StrategicObjectiveController extends Controller
{
    public function index(): View
    {
        return view('strategic-objectives.index', [
            'strategicObjectives' => StrategicObjective::query()
                ->withCount('opportunities')
                ->orderByDesc('active')
                ->orderByDesc('priority')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('strategic-objectives.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $strategicObjective = StrategicObjective::create($this->validatedStrategicObjective($request));

        return redirect()
            ->route('strategic-objectives.show', $strategicObjective)
            ->with('status', 'Strategic objective created.');
    }

    public function show(StrategicObjective $strategicObjective): View
    {
        $strategicObjective->load([
            'opportunities.actions' => fn ($query) => $query->orderByRaw('due_date is null')->orderBy('due_date')->orderBy('id'),
            'opportunities.opportunityGaps',
        ]);

        $linkedOpportunities = $strategicObjective->opportunities
            ->sortByDesc(fn (Opportunity $opportunity) => $opportunity->computedScore() ?? PHP_INT_MIN)
            ->values();

        return view('strategic-objectives.show', [
            'strategicObjective' => $strategicObjective,
            'linkedOpportunities' => $linkedOpportunities,
        ]);
    }

    public function edit(StrategicObjective $strategicObjective): View
    {
        return view('strategic-objectives.edit', [
            'strategicObjective' => $strategicObjective,
        ]);
    }

    public function update(Request $request, StrategicObjective $strategicObjective): RedirectResponse
    {
        $strategicObjective->update($this->validatedStrategicObjective($request));

        return redirect()
            ->route('strategic-objectives.show', $strategicObjective)
            ->with('status', 'Strategic objective updated.');
    }

    public function destroy(StrategicObjective $strategicObjective): RedirectResponse
    {
        $strategicObjective->delete();

        return redirect()
            ->route('strategic-objectives.index')
            ->with('status', 'Strategic objective deleted.');
    }

    private function validatedStrategicObjective(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', 'integer', 'min:1', 'max:10'],
            'active' => ['nullable', 'boolean'],
        ]) + ['active' => false];
    }
}
