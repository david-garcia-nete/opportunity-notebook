<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-indigo-600">Opportunity Strategic Context</p>
                <h2 class="text-2xl font-semibold leading-tight text-gray-900">
                    {{ $context['identity']['title'] }}
                </h2>
                <p class="mt-1 text-sm text-gray-500">A deterministic strategy data snapshot for this opportunity.</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('opportunities.show', $opportunity) }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Back to Opportunity
                </a>
                <a href="{{ route('opportunities.edit', $opportunity) }}" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Edit
                </a>
            </div>
        </div>
    </x-slot>

    <div class="bg-gray-50 py-10">
        <div class="mx-auto max-w-5xl space-y-8 px-4 sm:px-6 lg:px-8">
            <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Identity</p>
                    <h3 class="mt-1 text-lg font-semibold text-gray-900">Opportunity identity and status</h3>
                </div>
                <dl class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-100">
                        <dt class="text-sm font-medium text-gray-500">Company</dt>
                        <dd class="mt-1 font-semibold text-gray-900">{{ $context['identity']['company'] ?? '—' }}</dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-100">
                        <dt class="text-sm font-medium text-gray-500">Type</dt>
                        <dd class="mt-1 font-semibold text-gray-900">{{ $context['identity']['type'] ?? '—' }}</dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-100">
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1 font-semibold text-gray-900">{{ $context['identity']['status'] }}</dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-100">
                        <dt class="text-sm font-medium text-gray-500">Focus State</dt>
                        <dd class="mt-1 font-semibold text-gray-900">{{ $context['focus']['state'] }}</dd>
                    </div>
                </dl>
                <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-xl bg-indigo-50 p-4 ring-1 ring-inset ring-indigo-100">
                        <dt class="text-sm font-medium text-indigo-700">Focus Reason</dt>
                        <dd class="mt-1 whitespace-pre-line text-sm text-indigo-900">{{ $context['focus']['reason'] ?: 'No focus reason recorded.' }}</dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-100">
                        <dt class="text-sm font-medium text-gray-500">Notes</dt>
                        <dd class="mt-1 whitespace-pre-line text-sm text-gray-700">{{ $context['identity']['notes'] ?: 'No notes recorded.' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Scores</p>
                    <h3 class="mt-1 text-lg font-semibold text-gray-900">Scoring, readiness, and forecast</h3>
                </div>
                <dl class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        'Manual Score' => $context['scores']['manual_score'] ?? '—',
                        'Computed Score' => $context['scores']['computed_score'] ?? '—',
                        'Weighted Score' => $context['scores']['weighted_score'] ?? '—',
                        'Forecast Score' => $context['scores']['forecast_score'],
                        'Forecast Status' => $context['scores']['forecast_status'],
                        'Readiness Score' => $context['scores']['readiness_score'],
                        'Readiness Status' => $context['scores']['readiness_status'],
                    ] as $label => $value)
                        <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-100">
                            <dt class="text-sm font-medium text-gray-500">{{ $label }}</dt>
                            <dd class="mt-1 text-lg font-semibold text-gray-900">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            <div class="grid gap-8 lg:grid-cols-2">
                <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">Themes</h3>
                    <div class="mt-4 space-y-3">
                        @forelse ($context['themes'] as $theme)
                            <article class="rounded-xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-100">
                                <div class="flex items-center justify-between gap-3">
                                    <h4 class="font-semibold text-gray-900">{{ $theme['name'] }}</h4>
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Priority {{ $theme['priority'] ?? '—' }}</span>
                                </div>
                                <p class="mt-2 text-sm text-gray-600">{{ $theme['description'] ?: 'No description.' }}</p>
                            </article>
                        @empty
                            <p class="rounded-xl bg-slate-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-slate-100">No themes linked.</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">Strategic Objectives</h3>
                    <div class="mt-4 space-y-3">
                        @forelse ($context['strategic_objectives'] as $objective)
                            <article class="rounded-xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-100">
                                <div class="flex items-center justify-between gap-3">
                                    <h4 class="font-semibold text-gray-900">{{ $objective['name'] }}</h4>
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Priority {{ $objective['priority'] ?? '—' }}</span>
                                </div>
                                <p class="mt-2 text-sm text-gray-600">{{ $objective['description'] ?: 'No description.' }}</p>
                            </article>
                        @empty
                            <p class="rounded-xl bg-slate-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-slate-100">No strategic objectives linked.</p>
                        @endforelse
                    </div>
                </section>
            </div>

            <div class="grid gap-8 lg:grid-cols-2">
                <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">Open Actions</h3>
                    <div class="mt-4 space-y-3">
                        @forelse ($context['actions']['open'] as $action)
                            <article class="rounded-xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-100">
                                <h4 class="font-semibold text-gray-900">{{ $action['title'] }}</h4>
                                <dl class="mt-2 grid gap-2 text-sm sm:grid-cols-2">
                                    <div><dt class="text-gray-500">Status</dt><dd class="font-medium text-gray-900">{{ $action['status'] }}</dd></div>
                                    <div><dt class="text-gray-500">Due</dt><dd class="font-medium text-gray-900">{{ $action['due_date'] ?? 'No due date' }}</dd></div>
                                </dl>
                            </article>
                        @empty
                            <p class="rounded-xl bg-slate-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-slate-100">No open actions.</p>
                        @endforelse
                    </div>

                    <h3 class="mt-8 text-lg font-semibold text-gray-900">Completed Recent Actions</h3>
                    <div class="mt-4 space-y-3">
                        @forelse ($context['actions']['completed_recent'] as $action)
                            <article class="rounded-xl bg-green-50 p-4 ring-1 ring-inset ring-green-100">
                                <h4 class="font-semibold text-green-950">{{ $action['title'] }}</h4>
                                <p class="mt-1 text-sm text-green-800">Completed {{ $action['completed_at'] ?? 'recently' }}</p>
                            </article>
                        @empty
                            <p class="rounded-xl bg-slate-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-slate-100">No recently completed actions.</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">Open Gaps</h3>
                    <div class="mt-4 space-y-3">
                        @forelse ($context['gaps']['open'] as $gap)
                            <article class="rounded-xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-100">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h4 class="font-semibold text-gray-900">{{ $gap['title'] }}</h4>
                                    <span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-gray-600 ring-1 ring-inset ring-gray-200">{{ $gap['priority'] }}</span>
                                    <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-100">{{ $gap['category'] }}</span>
                                </div>
                                <p class="mt-2 text-sm text-gray-600">{{ $gap['description'] ?: 'No description.' }}</p>
                            </article>
                        @empty
                            <p class="rounded-xl bg-slate-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-slate-100">No open gaps.</p>
                        @endforelse
                    </div>

                    <h3 class="mt-8 text-lg font-semibold text-gray-900">Critical Gaps</h3>
                    <div class="mt-4 space-y-3">
                        @forelse ($context['gaps']['critical'] as $gap)
                            <article class="rounded-xl bg-red-50 p-4 ring-1 ring-inset ring-red-100">
                                <h4 class="font-semibold text-red-950">{{ $gap['title'] }}</h4>
                                <p class="mt-1 text-sm text-red-800">{{ $gap['description'] ?: 'No description.' }}</p>
                            </article>
                        @empty
                            <p class="rounded-xl bg-slate-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-slate-100">No critical open gaps.</p>
                        @endforelse
                    </div>
                </section>
            </div>

            <div class="grid gap-8 lg:grid-cols-2">
                <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">Contacts</h3>
                    <div class="mt-4 space-y-3">
                        @forelse ($context['contacts'] as $contact)
                            <article class="rounded-xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-100">
                                <h4 class="font-semibold text-gray-900">{{ $contact['name'] }}</h4>
                                <p class="mt-1 text-sm text-gray-600">{{ $contact['organization'] ?: 'No organization' }} · {{ $contact['relationship_type'] ?: 'No relationship type' }}</p>
                            </article>
                        @empty
                            <p class="rounded-xl bg-slate-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-slate-100">No contacts linked.</p>
                        @endforelse
                    </div>

                    <h3 class="mt-8 text-lg font-semibold text-gray-900">Recent Contact Interactions</h3>
                    <div class="mt-4 space-y-3">
                        @forelse ($context['recent_contact_interactions'] as $interaction)
                            <article class="rounded-xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-100">
                                <div class="flex items-center justify-between gap-3">
                                    <h4 class="font-semibold text-gray-900">{{ $interaction['contact_name'] ?? 'Unknown contact' }}</h4>
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $interaction['interaction_date'] }}</span>
                                </div>
                                <p class="mt-1 text-sm font-medium text-gray-700">{{ $interaction['interaction_type'] }}</p>
                                <p class="mt-2 whitespace-pre-line text-sm text-gray-600">{{ $interaction['summary'] }}</p>
                            </article>
                        @empty
                            <p class="rounded-xl bg-slate-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-slate-100">No contact interactions recorded.</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Decisions</h3>
                    <div class="mt-4 space-y-3">
                        @forelse ($context['decisions']['recent'] as $decision)
                            <article class="rounded-xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-100">
                                <div class="flex items-center justify-between gap-3">
                                    <h4 class="font-semibold text-gray-900">{{ $decision['decision_type_label'] }}</h4>
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $decision['decided_at'] }}</span>
                                </div>
                                <p class="mt-1 text-sm text-gray-600">{{ $decision['reason_category_label'] }}</p>
                                <p class="mt-2 whitespace-pre-line text-sm text-gray-600">{{ $decision['notes'] ?: 'No notes.' }}</p>
                            </article>
                        @empty
                            <p class="rounded-xl bg-slate-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-slate-100">No decisions recorded.</p>
                        @endforelse
                    </div>

                    <h3 class="mt-8 text-lg font-semibold text-gray-900">Latest Review-Linked Decisions</h3>
                    <div class="mt-4 space-y-3">
                        @forelse ($context['decisions']['latest_review_linked'] as $decision)
                            <article class="rounded-xl bg-indigo-50 p-4 ring-1 ring-inset ring-indigo-100">
                                <h4 class="font-semibold text-indigo-950">{{ $decision['decision_type_label'] }} · {{ $decision['review_type'] ? \Illuminate\Support\Str::headline($decision['review_type']) : 'Review' }}</h4>
                                <p class="mt-1 text-sm text-indigo-800">{{ $decision['reason_category_label'] }}</p>
                            </article>
                        @empty
                            <p class="rounded-xl bg-slate-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-slate-100">No review-linked decisions recorded.</p>
                        @endforelse
                    </div>
                </section>
            </div>

            <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Outcome Learning</p>
                    <h3 class="mt-1 text-lg font-semibold text-gray-900">Outcome, reason, and lesson learned</h3>
                </div>
                <dl class="mt-5 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-100">
                        <dt class="text-sm font-medium text-gray-500">Outcome</dt>
                        <dd class="mt-1 font-semibold text-gray-900">{{ $context['outcome_learning']['outcome'] ?? 'No outcome yet' }}</dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-100">
                        <dt class="text-sm font-medium text-gray-500">Outcome Reason</dt>
                        <dd class="mt-1 font-semibold text-gray-900">{{ $context['outcome_learning']['outcome_reason_label'] ?? 'No reason recorded' }}</dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-100">
                        <dt class="text-sm font-medium text-gray-500">Outcome Date</dt>
                        <dd class="mt-1 font-semibold text-gray-900">{{ $context['outcome_learning']['outcome_date'] ?? '—' }}</dd>
                    </div>
                    <div class="sm:col-span-3 rounded-xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-100">
                        <dt class="text-sm font-medium text-gray-500">Lesson Learned</dt>
                        <dd class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-700">{{ $context['outcome_learning']['lesson_learned'] ?: 'No lesson recorded.' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Timeline Summary</p>
                    <h3 class="mt-1 text-lg font-semibold text-gray-900">Recent history and upcoming items</h3>
                </div>
                <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-100">
                        <dt class="text-sm font-medium text-gray-500">Upcoming Items</dt>
                        <dd class="mt-1 text-2xl font-bold text-gray-900">{{ $context['timeline_summary']['upcoming_count'] }}</dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-100">
                        <dt class="text-sm font-medium text-gray-500">History Items</dt>
                        <dd class="mt-1 text-2xl font-bold text-gray-900">{{ $context['timeline_summary']['history_count'] }}</dd>
                    </div>
                </dl>
                <div class="mt-5 grid gap-6 lg:grid-cols-2">
                    <div>
                        <h4 class="font-semibold text-gray-900">Next Upcoming</h4>
                        <div class="mt-3 space-y-3">
                            @forelse ($context['timeline_summary']['next_upcoming'] as $item)
                                <article class="rounded-xl bg-slate-50 p-4 text-sm ring-1 ring-inset ring-slate-100">
                                    <p class="font-semibold text-gray-900">{{ $item['title'] }}</p>
                                    <p class="mt-1 text-gray-600">{{ $item['type_label'] }} · {{ $item['date'] }}</p>
                                </article>
                            @empty
                                <p class="rounded-xl bg-slate-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-slate-100">No upcoming timeline items.</p>
                            @endforelse
                        </div>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900">Recent History</h4>
                        <div class="mt-3 space-y-3">
                            @forelse ($context['timeline_summary']['recent_history'] as $item)
                                <article class="rounded-xl bg-slate-50 p-4 text-sm ring-1 ring-inset ring-slate-100">
                                    <p class="font-semibold text-gray-900">{{ $item['title'] }}</p>
                                    <p class="mt-1 text-gray-600">{{ $item['type_label'] }} · {{ $item['date'] }}</p>
                                </article>
                            @empty
                                <p class="rounded-xl bg-slate-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-slate-100">No timeline history yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
