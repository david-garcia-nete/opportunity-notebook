<?php

namespace App\Http\Controllers;

use App\Models\Opportunity;
use App\Models\OpportunityGap;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OpportunityGapController extends Controller
{
    public function create(Opportunity $opportunity): View
    {
        return view('opportunity-gaps.create', [
            'categories' => OpportunityGap::CATEGORIES,
            'opportunity' => $opportunity,
            'priorities' => OpportunityGap::PRIORITIES,
            'statuses' => OpportunityGap::STATUSES,
        ]);
    }

    public function store(Request $request, Opportunity $opportunity): RedirectResponse
    {
        $opportunity->opportunityGaps()->create($this->validatedOpportunityGap($request));

        return redirect()
            ->route('opportunities.show', $opportunity)
            ->with('status', 'Opportunity gap created.');
    }

    public function edit(Opportunity $opportunity, OpportunityGap $gap): View
    {
        $this->authorizeOpportunityGap($opportunity, $gap);

        return view('opportunity-gaps.edit', [
            'categories' => OpportunityGap::CATEGORIES,
            'gap' => $gap,
            'opportunity' => $opportunity,
            'priorities' => OpportunityGap::PRIORITIES,
            'statuses' => OpportunityGap::STATUSES,
        ]);
    }

    public function update(Request $request, Opportunity $opportunity, OpportunityGap $gap): RedirectResponse
    {
        $this->authorizeOpportunityGap($opportunity, $gap);

        $gap->update($this->validatedOpportunityGap($request));

        return redirect()
            ->route('opportunities.show', $opportunity)
            ->with('status', 'Opportunity gap updated.');
    }

    public function destroy(Opportunity $opportunity, OpportunityGap $gap): RedirectResponse
    {
        $this->authorizeOpportunityGap($opportunity, $gap);

        $gap->delete();

        return redirect()
            ->route('opportunities.show', $opportunity)
            ->with('status', 'Opportunity gap deleted.');
    }

    private function validatedOpportunityGap(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'string', Rule::in(OpportunityGap::CATEGORIES)],
            'status' => ['required', 'string', Rule::in(OpportunityGap::STATUSES)],
            'priority' => ['required', 'string', Rule::in(OpportunityGap::PRIORITIES)],
        ]);
    }

    private function authorizeOpportunityGap(Opportunity $opportunity, OpportunityGap $gap): void
    {
        abort_unless($gap->opportunity_id === $opportunity->id, 404);
    }
}
