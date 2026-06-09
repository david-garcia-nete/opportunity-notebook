<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Opportunity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactOpportunityController extends Controller
{
    public function storeForOpportunity(Request $request, Opportunity $opportunity): RedirectResponse
    {
        $validated = $request->validate([
            'contact_id' => ['required', 'integer', Rule::exists('contacts', 'id')],
            'relationship_type' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $opportunity->contacts()->syncWithoutDetaching([
            $validated['contact_id'] => [
                'relationship_type' => $validated['relationship_type'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ],
        ]);

        return redirect()
            ->route('opportunities.show', $opportunity)
            ->with('status', 'Contact linked to opportunity.');
    }

    public function destroyFromOpportunity(Opportunity $opportunity, Contact $contact): RedirectResponse
    {
        $opportunity->contacts()->detach($contact->id);

        return redirect()
            ->route('opportunities.show', $opportunity)
            ->with('status', 'Contact removed from opportunity.');
    }

    public function storeForContact(Request $request, Contact $contact): RedirectResponse
    {
        $validated = $request->validate([
            'opportunity_id' => ['required', 'integer', Rule::exists('opportunities', 'id')],
            'relationship_type' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $contact->opportunities()->syncWithoutDetaching([
            $validated['opportunity_id'] => [
                'relationship_type' => $validated['relationship_type'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ],
        ]);

        return redirect()
            ->route('contacts.show', $contact)
            ->with('status', 'Opportunity linked to contact.');
    }

    public function destroyFromContact(Contact $contact, Opportunity $opportunity): RedirectResponse
    {
        $contact->opportunities()->detach($opportunity->id);

        return redirect()
            ->route('contacts.show', $contact)
            ->with('status', 'Opportunity removed from contact.');
    }
}
