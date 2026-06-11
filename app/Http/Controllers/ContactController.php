<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Opportunity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(): View
    {
        return view('contacts.index', [
            'contacts' => Contact::latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('contacts.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $contact = Contact::create($this->validatedContact($request));

        return redirect()
            ->route('contacts.show', $contact)
            ->with('status', 'Contact created.');
    }

    public function show(Contact $contact): View
    {
        return view('contacts.show', [
            'availableOpportunities' => Opportunity::orderBy('title')->get(),
            'contact' => $contact->load([
                'contactInteractions' => fn ($query) => $query->with('opportunity')->latest('interaction_date')->latest(),
                'opportunities' => fn ($query) => $query->orderBy('title'),
            ]),
        ]);
    }

    public function edit(Contact $contact): View
    {
        return view('contacts.edit', [
            'contact' => $contact,
        ]);
    }

    public function update(Request $request, Contact $contact): RedirectResponse
    {
        $contact->update($this->validatedContact($request));

        return redirect()
            ->route('contacts.show', $contact)
            ->with('status', 'Contact updated.');
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        $contact->delete();

        return redirect()
            ->route('contacts.index')
            ->with('status', 'Contact deleted.');
    }

    private function validatedContact(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'organization' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
