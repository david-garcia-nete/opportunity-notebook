<?php

namespace App\Http\Controllers;

use App\Models\Opportunity;
use App\Models\StrategicObjective;
use App\Models\Theme;
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
        return view('strategic-objectives.create', [
            'availableThemes' => $this->availableThemes(),
            'selectedThemes' => collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $strategicObjective = StrategicObjective::create($this->validatedStrategicObjective($request));
        $strategicObjective->themes()->sync($request->input('theme_ids', []));

        return redirect()
            ->route('strategic-objectives.show', $strategicObjective)
            ->with('status', 'Strategic objective created.');
    }

    public function show(StrategicObjective $strategicObjective): View
    {
        $strategicObjective->load([
            'opportunities.actions' => fn ($query) => $query->orderByRaw('due_date is null')->orderBy('due_date')->orderBy('id'),
            'opportunities.opportunityGaps',
            'themes' => fn ($query) => $query->orderByRaw('priority is null')->orderBy('priority')->orderBy('name'),
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
            'availableThemes' => $this->availableThemes($strategicObjective),
            'selectedThemes' => $strategicObjective->themes,
            'strategicObjective' => $strategicObjective,
        ]);
    }

    public function update(Request $request, StrategicObjective $strategicObjective): RedirectResponse
    {
        $strategicObjective->update($this->validatedStrategicObjective($request));
        $strategicObjective->themes()->sync($request->input('theme_ids', []));

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
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', 'integer', 'min:1', 'max:10'],
            'active' => ['nullable', 'boolean'],
            'theme_ids' => ['nullable', 'array'],
            'theme_ids.*' => ['integer', 'exists:themes,id'],
        ]) + ['active' => false];

        unset($validated['theme_ids']);

        return $validated;
    }

    private function availableThemes(?StrategicObjective $strategicObjective = null)
    {
        return Theme::query()
            ->where('active', true)
            ->when($strategicObjective, fn ($query) => $query->orWhereIn('id', $strategicObjective->themes()->pluck('themes.id')))
            ->orderByDesc('active')
            ->orderByRaw('priority is null')
            ->orderBy('priority')
            ->orderBy('name')
            ->get();
    }
}
