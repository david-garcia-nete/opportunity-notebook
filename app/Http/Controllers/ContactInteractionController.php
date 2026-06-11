<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactInteraction;
use App\Models\Opportunity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContactInteractionController extends Controller
{
    public function create(Request $request): View
    {
        return view('contact-interactions.create', [
            'contacts' => Contact::orderBy('name')->get(),
            'interaction' => new ContactInteraction([
                'contact_id' => $request->integer('contact_id') ?: null,
                'opportunity_id' => $request->integer('opportunity_id') ?: null,
                'interaction_date' => today(),
            ]),
            'interactionTypes' => ContactInteraction::TYPES,
            'opportunities' => Opportunity::orderBy('title')->get(),
            'redirectTo' => $this->redirectTarget($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $interaction = ContactInteraction::create($this->validatedInteraction($request));

        return redirect($this->redirectTarget($request, $interaction))
            ->with('status', 'Contact interaction created.');
    }

    public function edit(ContactInteraction $contactInteraction, Request $request): View
    {
        return view('contact-interactions.edit', [
            'contacts' => Contact::orderBy('name')->get(),
            'interaction' => $contactInteraction,
            'interactionTypes' => ContactInteraction::TYPES,
            'opportunities' => Opportunity::orderBy('title')->get(),
            'redirectTo' => $this->redirectTarget($request, $contactInteraction),
        ]);
    }

    public function update(Request $request, ContactInteraction $contactInteraction): RedirectResponse
    {
        $contactInteraction->update($this->validatedInteraction($request));

        return redirect($this->redirectTarget($request, $contactInteraction))
            ->with('status', 'Contact interaction updated.');
    }

    public function destroy(Request $request, ContactInteraction $contactInteraction): RedirectResponse
    {
        $redirectTo = $this->redirectTarget($request, $contactInteraction);

        $contactInteraction->delete();

        return redirect($redirectTo)
            ->with('status', 'Contact interaction deleted.');
    }

    private function validatedInteraction(Request $request): array
    {
        return $request->validate([
            'contact_id' => ['required', 'exists:contacts,id'],
            'opportunity_id' => ['nullable', 'exists:opportunities,id'],
            'interaction_date' => ['required', 'date'],
            'interaction_type' => ['required', 'string', Rule::in(ContactInteraction::TYPES)],
            'summary' => ['required', 'string'],
            'outcome' => ['nullable', 'string'],
            'next_follow_up_date' => ['nullable', 'date'],
        ]);
    }

    private function redirectTarget(Request $request, ?ContactInteraction $interaction = null): string
    {
        $redirectTo = $request->input('redirect_to', $request->query('redirect_to'));

        if ($redirectTo === 'opportunity' && ($interaction?->opportunity_id || $request->integer('opportunity_id'))) {
            return route('opportunities.show', $interaction?->opportunity_id ?? $request->integer('opportunity_id'));
        }

        if ($interaction?->contact_id || $request->integer('contact_id')) {
            return route('contacts.show', $interaction?->contact_id ?? $request->integer('contact_id'));
        }

        return route('contacts.index');
    }
}
