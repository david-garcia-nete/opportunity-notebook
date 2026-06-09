<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Opportunity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OpportunityController extends Controller
{
    public function index(): View
    {
        return view('opportunities.index', [
            'opportunities' => Opportunity::latest()->get(),
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
            'opportunity' => $opportunity->load([
                'actions' => fn ($query) => $query->latest('due_date')->latest(),
                'applications' => fn ($query) => $query->latest('applied_at')->latest(),
                'contacts' => fn ($query) => $query->orderBy('name'),
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
            'notes' => ['nullable', 'string'],
        ]);
    }
}
