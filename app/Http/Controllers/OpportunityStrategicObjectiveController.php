<?php

namespace App\Http\Controllers;

use App\Models\Opportunity;
use App\Models\StrategicObjective;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OpportunityStrategicObjectiveController extends Controller
{
    public function storeForOpportunity(Request $request, Opportunity $opportunity): RedirectResponse
    {
        $validated = $request->validate([
            'strategic_objective_id' => ['required', 'integer', Rule::exists('strategic_objectives', 'id')],
        ]);

        $opportunity->strategicObjectives()->syncWithoutDetaching($validated['strategic_objective_id']);

        return redirect()
            ->route('opportunities.show', $opportunity)
            ->with('status', 'Strategic objective linked to opportunity.');
    }

    public function destroyFromOpportunity(Opportunity $opportunity, StrategicObjective $strategicObjective): RedirectResponse
    {
        $opportunity->strategicObjectives()->detach($strategicObjective->id);

        return redirect()
            ->route('opportunities.show', $opportunity)
            ->with('status', 'Strategic objective removed from opportunity.');
    }
}
