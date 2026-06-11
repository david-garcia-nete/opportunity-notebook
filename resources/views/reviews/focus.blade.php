<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-indigo-600">Guided Focus Review</p>
                <h2 class="text-2xl font-semibold leading-tight text-gray-900">
                    Decide what deserves focused effort now
                </h2>
            </div>
            <a href="{{ route('reviews.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Back to Reviews
            </a>
        </div>
    </x-slot>

    <div class="bg-gray-50 py-10">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-md bg-green-50 p-4 text-sm font-medium text-green-700 ring-1 ring-inset ring-green-200">
                    {{ session('status') }}
                </div>
            @endif

            <div class="mb-8 rounded-2xl border border-indigo-100 bg-white p-6 shadow-sm">
                <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Review workflow</p>
                <h3 class="mt-1 text-lg font-semibold text-gray-900">Review each current focus opportunity, record the strategic decision, and add the next action when useful.</h3>
                <p class="mt-2 text-sm leading-6 text-gray-600">Decisions created here will be linked to this Review session so the completed review becomes a record of focus choices, rationale, and follow-through.</p>
            </div>

            @if ($opportunities->isEmpty())
                <div class="rounded-2xl border border-gray-100 bg-white p-8 text-center shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">No current focus opportunities</h3>
                    <p class="mt-2 text-sm text-gray-600">Mark an active opportunity as focus before starting a guided focus review.</p>
                </div>
            @else
                <form method="POST" action="{{ route('reviews.focus.complete', $review) }}" class="space-y-8">
                    @csrf
                    <x-input-error :messages="$errors->get('decisions')" class="rounded-md bg-red-50 p-4 text-sm font-medium text-red-700 ring-1 ring-inset ring-red-200" />

                    @foreach ($opportunities as $opportunity)
                        @php($opportunitySignals = $signals[$opportunity->id])
                        <article class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                            <div class="border-b border-gray-100 bg-slate-50 p-6">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div>
                                        <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Focus Opportunity</p>
                                        <h3 class="mt-1 text-xl font-semibold text-gray-900">
                                            <a href="{{ route('opportunities.show', $opportunity) }}" class="text-indigo-700 hover:text-indigo-900">{{ $opportunity->title }}</a>
                                        </h3>
                                        @if ($opportunity->company)
                                            <p class="mt-1 text-sm text-gray-600">{{ $opportunity->company }}</p>
                                        @endif
                                    </div>
                                    <dl class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                                        <div class="rounded-xl bg-white p-3 ring-1 ring-inset ring-gray-100">
                                            <dt class="text-gray-500">Weighted</dt>
                                            <dd class="mt-1 font-semibold text-gray-900">{{ $opportunitySignals['weighted_score'] ?? '—' }}</dd>
                                        </div>
                                        <div class="rounded-xl bg-white p-3 ring-1 ring-inset ring-gray-100">
                                            <dt class="text-gray-500">Computed</dt>
                                            <dd class="mt-1 font-semibold text-gray-900">{{ $opportunitySignals['computed_score'] ?? '—' }}</dd>
                                        </div>
                                        <div class="rounded-xl bg-white p-3 ring-1 ring-inset ring-gray-100">
                                            <dt class="text-gray-500">Forecast</dt>
                                            <dd class="mt-1 font-semibold text-gray-900">{{ $opportunitySignals['forecast_status'] }} · {{ $opportunitySignals['forecast_score'] }}</dd>
                                        </div>
                                        <div class="rounded-xl bg-white p-3 ring-1 ring-inset ring-gray-100">
                                            <dt class="text-gray-500">Readiness</dt>
                                            <dd class="mt-1 font-semibold text-gray-900">{{ $opportunitySignals['readiness_status'] }} · {{ $opportunitySignals['readiness_score'] }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>

                            <div class="grid gap-6 p-6 lg:grid-cols-3">
                                <section class="space-y-4 lg:col-span-2">
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-100">
                                            <h4 class="text-sm font-semibold text-gray-900">Open gaps</h4>
                                            @if ($opportunitySignals['open_gaps']->isEmpty())
                                                <p class="mt-2 text-sm text-gray-600">No open gaps.</p>
                                            @else
                                                <ul class="mt-2 space-y-2 text-sm text-gray-700">
                                                    @foreach ($opportunitySignals['open_gaps'] as $gap)
                                                        <li>{{ $gap->priority }} · {{ $gap->title }}</li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </div>

                                        <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-100">
                                            <h4 class="text-sm font-semibold text-gray-900">Execution signals</h4>
                                            @if ($opportunitySignals['missing_next_action'])
                                                <p class="mt-2 text-sm font-semibold text-amber-700">Missing next action</p>
                                            @else
                                                <p class="mt-2 text-sm text-gray-600">Next action is present.</p>
                                            @endif
                                            @if ($opportunitySignals['overdue_actions']->isEmpty())
                                                <p class="mt-2 text-sm text-gray-600">No overdue actions.</p>
                                            @else
                                                <ul class="mt-2 space-y-2 text-sm text-red-700">
                                                    @foreach ($opportunitySignals['overdue_actions'] as $action)
                                                        <li>{{ $action->title }} · due {{ $action->due_date->toFormattedDateString() }}</li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-100">
                                            <h4 class="text-sm font-semibold text-gray-900">Recent activity</h4>
                                            @if ($opportunitySignals['recent_activity']->isEmpty())
                                                <p class="mt-2 text-sm text-gray-600">No recent activity.</p>
                                            @else
                                                <ul class="mt-2 space-y-2 text-sm text-gray-700">
                                                    @foreach ($opportunitySignals['recent_activity'] as $activity)
                                                        <li>{{ $activity['type_label'] }} · {{ $activity['title'] }}</li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </div>

                                        <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-100">
                                            <h4 class="text-sm font-semibold text-gray-900">Contacts and follow-ups</h4>
                                            @if ($opportunity->contacts->isEmpty() && $opportunitySignals['recent_contact_interactions']->isEmpty() && $opportunitySignals['follow_ups']->isEmpty())
                                                <p class="mt-2 text-sm text-gray-600">No linked contacts, recent interactions, or upcoming follow-ups.</p>
                                            @else
                                                <ul class="mt-2 space-y-2 text-sm text-gray-700">
                                                    @foreach ($opportunity->contacts as $contact)
                                                        <li>{{ $contact->name }}@if($contact->organization) · {{ $contact->organization }}@endif</li>
                                                    @endforeach
                                                    @foreach ($opportunitySignals['recent_contact_interactions'] as $interaction)
                                                        <li>
                                                            Recent interaction: {{ $interaction->contact?->name ?? 'Contact' }} · {{ $interaction->interaction_date->toFormattedDateString() }}
                                                            <span class="block text-gray-600">{{ $interaction->summary }}</span>
                                                        </li>
                                                    @endforeach
                                                    @foreach ($opportunitySignals['follow_ups'] as $followUp)
                                                        <li>Follow up: {{ $followUp->contact?->name ?? 'Contact' }} · {{ $followUp->next_follow_up_date->toFormattedDateString() }}</li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </div>
                                    </div>
                                </section>

                                <section class="rounded-xl border border-gray-100 p-4">
                                    <h4 class="text-sm font-semibold text-gray-900">Decision</h4>
                                    <div class="mt-4 space-y-4">
                                        <div>
                                            <label for="decision_type_{{ $opportunity->id }}" class="block text-sm font-medium text-gray-700">Decision Type</label>
                                            <select id="decision_type_{{ $opportunity->id }}" name="decisions[{{ $opportunity->id }}][decision_type]" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                <option value="">Choose decision</option>
                                                @foreach ($decisionTypes as $value => $label)
                                                    <option value="{{ $value }}" @selected(old("decisions.{$opportunity->id}.decision_type") === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <x-input-error :messages="$errors->get('decisions.'.$opportunity->id.'.decision_type')" class="mt-2" />
                                        </div>

                                        <div>
                                            <label for="reason_category_{{ $opportunity->id }}" class="block text-sm font-medium text-gray-700">Reason Category</label>
                                            <select id="reason_category_{{ $opportunity->id }}" name="decisions[{{ $opportunity->id }}][reason_category]" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                <option value="">Choose reason</option>
                                                @foreach ($reasonCategories as $value => $label)
                                                    <option value="{{ $value }}" @selected(old("decisions.{$opportunity->id}.reason_category") === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <x-input-error :messages="$errors->get('decisions.'.$opportunity->id.'.reason_category')" class="mt-2" />
                                        </div>

                                        <div>
                                            <label for="notes_{{ $opportunity->id }}" class="block text-sm font-medium text-gray-700">Notes</label>
                                            <textarea id="notes_{{ $opportunity->id }}" name="decisions[{{ $opportunity->id }}][notes]" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old("decisions.{$opportunity->id}.notes") }}</textarea>
                                            <x-input-error :messages="$errors->get('decisions.'.$opportunity->id.'.notes')" class="mt-2" />
                                        </div>
                                    </div>

                                    <div class="mt-6 border-t border-gray-100 pt-4">
                                        <h5 class="text-sm font-semibold text-gray-900">Optional next action</h5>
                                        <div class="mt-4 space-y-4">
                                            <div>
                                                <label for="action_title_{{ $opportunity->id }}" class="block text-sm font-medium text-gray-700">Action Title</label>
                                                <input id="action_title_{{ $opportunity->id }}" name="next_actions[{{ $opportunity->id }}][title]" type="text" value="{{ old("next_actions.{$opportunity->id}.title") }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                                <x-input-error :messages="$errors->get('next_actions.'.$opportunity->id.'.title')" class="mt-2" />
                                            </div>
                                            <div>
                                                <label for="action_due_date_{{ $opportunity->id }}" class="block text-sm font-medium text-gray-700">Due Date</label>
                                                <input id="action_due_date_{{ $opportunity->id }}" name="next_actions[{{ $opportunity->id }}][due_date]" type="date" value="{{ old("next_actions.{$opportunity->id}.due_date") }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                                <x-input-error :messages="$errors->get('next_actions.'.$opportunity->id.'.due_date')" class="mt-2" />
                                            </div>
                                            <div>
                                                <label for="action_description_{{ $opportunity->id }}" class="block text-sm font-medium text-gray-700">Description</label>
                                                <textarea id="action_description_{{ $opportunity->id }}" name="next_actions[{{ $opportunity->id }}][description]" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old("next_actions.{$opportunity->id}.description") }}</textarea>
                                                <x-input-error :messages="$errors->get('next_actions.'.$opportunity->id.'.description')" class="mt-2" />
                                            </div>
                                        </div>
                                    </div>
                                </section>
                            </div>
                        </article>
                    @endforeach

                    <div class="flex items-center justify-end gap-3 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                        <a href="{{ route('reviews.index') }}" class="text-sm font-semibold text-gray-600 hover:text-gray-900">Cancel</a>
                        <button type="submit" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            Complete Focus Review
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
