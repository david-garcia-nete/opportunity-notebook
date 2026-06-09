<?php

namespace App\Http\Controllers;

use App\Models\Opportunity;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OpportunityProjectController extends Controller
{
    public function storeForOpportunity(Request $request, Opportunity $opportunity): RedirectResponse
    {
        $validated = $request->validate([
            'project_id' => ['required', 'integer', Rule::exists('projects', 'id')],
            'notes' => ['nullable', 'string'],
        ]);

        $opportunity->projects()->syncWithoutDetaching([
            $validated['project_id'] => [
                'notes' => $validated['notes'] ?? null,
            ],
        ]);

        return redirect()
            ->route('opportunities.show', $opportunity)
            ->with('status', 'Project linked to opportunity.');
    }

    public function destroyFromOpportunity(Opportunity $opportunity, Project $project): RedirectResponse
    {
        $opportunity->projects()->detach($project->id);

        return redirect()
            ->route('opportunities.show', $opportunity)
            ->with('status', 'Project removed from opportunity.');
    }

    public function storeForProject(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'opportunity_id' => ['required', 'integer', Rule::exists('opportunities', 'id')],
            'notes' => ['nullable', 'string'],
        ]);

        $project->opportunities()->syncWithoutDetaching([
            $validated['opportunity_id'] => [
                'notes' => $validated['notes'] ?? null,
            ],
        ]);

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'Opportunity linked to project.');
    }

    public function destroyFromProject(Project $project, Opportunity $opportunity): RedirectResponse
    {
        $project->opportunities()->detach($opportunity->id);

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'Opportunity removed from project.');
    }
}
