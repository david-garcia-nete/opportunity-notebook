<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-indigo-600">Contact</p>
                <h2 class="text-2xl font-semibold leading-tight text-gray-900">
                    {{ $contact->name }}
                </h2>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('contacts.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Back
                </a>
                <a href="{{ route('contacts.edit', $contact) }}" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Edit
                </a>
            </div>
        </div>
    </x-slot>

    <div class="bg-gray-50 py-10">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-md bg-green-50 p-4 text-sm font-medium text-green-700 ring-1 ring-inset ring-green-200">
                    {{ session('status') }}
                </div>
            @endif

            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <dl class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Organization</dt>
                        <dd class="mt-1 text-base font-semibold text-gray-900">{{ $contact->organization ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                        <dd class="mt-1 text-base font-semibold text-gray-900">{{ $contact->email ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Phone</dt>
                        <dd class="mt-1 text-base font-semibold text-gray-900">{{ $contact->phone ?? '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Notes</dt>
                        <dd class="mt-2 whitespace-pre-line rounded-xl bg-gray-50 p-4 text-sm leading-6 text-gray-700 ring-1 ring-inset ring-gray-100">{{ $contact->notes ?: 'No notes yet.' }}</dd>
                    </div>
                </dl>
            </div>


            @php
                $lastInteraction = $contact->contactInteractions->first();
                $upcomingFollowUps = $contact->contactInteractions
                    ->filter(fn ($interaction) => $interaction->next_follow_up_date && ($interaction->next_follow_up_date->isToday() || $interaction->next_follow_up_date->isFuture()));
            @endphp

            <section class="mt-8 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Relationship Activity</h3>
                        <p class="mt-1 text-sm text-gray-500">A lightweight history of conversations, outcomes, and follow-ups.</p>
                    </div>
                    <a href="{{ route('contact-interactions.create', ['contact_id' => $contact->id, 'redirect_to' => 'contact']) }}" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Add Interaction
                    </a>
                </div>

                <dl class="mt-5 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-xl bg-gray-50 p-4 ring-1 ring-inset ring-gray-100">
                        <dt class="text-sm font-medium text-gray-500">Total interactions</dt>
                        <dd class="mt-2 text-2xl font-bold text-gray-900">{{ $contact->contactInteractions->count() }}</dd>
                    </div>
                    <div class="rounded-xl bg-gray-50 p-4 ring-1 ring-inset ring-gray-100">
                        <dt class="text-sm font-medium text-gray-500">Last interaction</dt>
                        <dd class="mt-2 text-2xl font-bold text-gray-900">{{ $lastInteraction?->interaction_date->toFormattedDateString() ?? '—' }}</dd>
                    </div>
                    <div class="rounded-xl bg-gray-50 p-4 ring-1 ring-inset ring-gray-100">
                        <dt class="text-sm font-medium text-gray-500">Upcoming follow-ups</dt>
                        <dd class="mt-2 text-2xl font-bold text-gray-900">{{ $upcomingFollowUps->count() }}</dd>
                    </div>
                </dl>

                <dl class="mt-5 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-xl bg-indigo-50 p-4 ring-1 ring-inset ring-indigo-100">
                        <dt class="text-sm font-medium text-indigo-700">Linked opportunities</dt>
                        <dd class="mt-2 text-2xl font-bold text-indigo-950">{{ $contact->linkedOpportunitiesCount() }}</dd>
                    </div>
                    <div class="rounded-xl bg-indigo-50 p-4 ring-1 ring-inset ring-indigo-100">
                        <dt class="text-sm font-medium text-indigo-700">Active opportunities</dt>
                        <dd class="mt-2 text-2xl font-bold text-indigo-950">{{ $contact->activeOpportunitiesCount() }}</dd>
                    </div>
                    <div class="rounded-xl bg-indigo-50 p-4 ring-1 ring-inset ring-indigo-100">
                        <dt class="text-sm font-medium text-indigo-700">Average opportunity score</dt>
                        <dd class="mt-2 text-2xl font-bold text-indigo-950">{{ $contact->averageOpportunityScore() ?? '—' }}</dd>
                    </div>
                </dl>

                @if ($contact->contactInteractions->isEmpty())
                    <div class="mt-5 rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">
                        No relationship activity recorded yet.
                    </div>
                @else
                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Date</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Type</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Opportunity</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Summary</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Outcome</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Follow-up</th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($contact->contactInteractions as $interaction)
                                    <tr>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm font-semibold text-gray-900">{{ $interaction->interaction_date->toFormattedDateString() }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $interaction->interaction_type }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">
                                            @if ($interaction->opportunity)
                                                <a href="{{ route('opportunities.show', $interaction->opportunity) }}" class="font-semibold text-indigo-600 hover:text-indigo-900">{{ $interaction->opportunity->title }}</a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 text-sm text-gray-600">{{ $interaction->summary }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-600">{{ $interaction->outcome ?? '—' }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $interaction->next_follow_up_date?->toFormattedDateString() ?? '—' }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-right text-sm">
                                            <div class="flex justify-end gap-3">
                                                <a href="{{ route('contact-interactions.edit', ['contact_interaction' => $interaction, 'redirect_to' => 'contact']) }}" class="font-semibold text-indigo-600 hover:text-indigo-900">Edit</a>
                                                <form method="POST" action="{{ route('contact-interactions.destroy', $interaction) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="redirect_to" value="contact">
                                                    <button type="submit" class="font-semibold text-red-600 hover:text-red-900">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="mt-8 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Opportunities</h3>
                    <p class="mt-1 text-sm text-gray-500">Income opportunities connected to this contact.</p>
                </div>

                <form method="POST" action="{{ route('contacts.opportunities.store', $contact) }}" class="mt-5 grid gap-4 rounded-xl bg-gray-50 p-4 ring-1 ring-inset ring-gray-100 sm:grid-cols-2">
                    @csrf
                    <div>
                        <label for="opportunity_id" class="block text-sm font-medium text-gray-700">Opportunity</label>
                        <select id="opportunity_id" name="opportunity_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Choose an opportunity</option>
                            @foreach ($availableOpportunities as $opportunity)
                                <option value="{{ $opportunity->id }}" @selected(old('opportunity_id') == $opportunity->id)>{{ $opportunity->title }}{{ $opportunity->company ? ' — '.$opportunity->company : '' }}</option>
                            @endforeach
                        </select>
                        @error('opportunity_id')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="relationship_type" class="block text-sm font-medium text-gray-700">Relationship Type</label>
                        <input id="relationship_type" name="relationship_type" type="text" value="{{ old('relationship_type') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Recruiter, referral, hiring manager">
                        @error('relationship_type')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="opportunity_notes" class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea id="opportunity_notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Context for this relationship">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            Attach Opportunity
                        </button>
                    </div>
                </form>

                @if ($contact->opportunities->isEmpty())
                    <div class="mt-5 rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">
                        No opportunities linked to this contact yet.
                    </div>
                @else
                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Title</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Company</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Relationship Type</th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($contact->opportunities as $opportunity)
                                    <tr>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm font-semibold text-gray-900">
                                            <a href="{{ route('opportunities.show', $opportunity) }}" class="text-indigo-600 hover:text-indigo-900">{{ $opportunity->title }}</a>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $opportunity->company ?? '—' }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $opportunity->status }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $opportunity->pivot->relationship_type ?? '—' }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-right text-sm">
                                            <form method="POST" action="{{ route('contacts.opportunities.destroy', [$contact, $opportunity]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="font-semibold text-red-600 hover:text-red-900">Detach</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

        </div>
    </div>
</x-app-layout>
