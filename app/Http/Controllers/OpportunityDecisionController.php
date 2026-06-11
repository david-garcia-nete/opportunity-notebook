<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOpportunityDecisionRequest;
use App\Models\Opportunity;
use Illuminate\Http\RedirectResponse;

class OpportunityDecisionController extends Controller
{
    public function store(StoreOpportunityDecisionRequest $request, Opportunity $opportunity): RedirectResponse
    {
        $opportunity->decisions()->create($request->validated());

        return redirect()
            ->route('opportunities.show', $opportunity)
            ->with('status', 'Decision recorded.');
    }
}
