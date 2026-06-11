<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-indigo-600">Weekly Review</p>
                <h2 class="text-2xl font-semibold leading-tight text-gray-900">
                    {{ __('Choose → Commit → Execute → Review → Adjust') }}
                </h2>
            </div>
            <p class="text-sm text-gray-500">Review the week and choose next week’s attention.</p>
        </div>
    </x-slot>

    <div class="bg-gray-50 py-10">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <section class="rounded-3xl bg-gradient-to-br from-slate-900 via-indigo-800 to-indigo-600 p-8 text-white shadow-xl">
                <p class="text-sm font-semibold uppercase tracking-wide text-indigo-100">Operating rhythm</p>
                <h1 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">What should change after this week?</h1>
                <div class="mt-6 grid gap-3 text-sm text-indigo-50 md:grid-cols-2">
                    <p>• Are these still the right focus opportunities?</p>
                    <p>• What changed this week?</p>
                    <p>• Which opportunity deserves the most attention next week?</p>
                    <p>• What should be parked or abandoned?</p>
                    <p>• What is the next concrete action?</p>
                </div>
            </section>

            <section class="rounded-2xl border border-indigo-100 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Current Focus Opportunities</p>
                        <h3 class="mt-1 text-lg font-semibold text-gray-900">What am I focused on?</h3>
                        <p class="mt-1 text-sm text-gray-500">Decide whether to continue, park, or abandon each focus opportunity.</p>
                    </div>
                    <a href="{{ route('opportunities.index', ['focus' => 1]) }}" class="inline-flex items-center justify-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Manage Focus
                    </a>
                </div>

                @if ($currentFocusOpportunities->isEmpty())
                    <div class="mt-5 rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">
                        No focus opportunities are selected yet.
                    </div>
                @else
                    <div class="mt-6 grid gap-4 lg:grid-cols-2">
                        @foreach ($currentFocusOpportunities as $opportunity)
                            @php($nextAction = $opportunity->nextAction())
                            <article class="rounded-xl bg-indigo-50 p-5 ring-1 ring-inset ring-indigo-100">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <a href="{{ route('opportunities.show', $opportunity) }}" class="text-base font-semibold text-indigo-700 hover:text-indigo-900">{{ $opportunity->title }}</a>
                                        <p class="mt-1 text-sm text-gray-700">{{ $opportunity->company ?? 'No company listed' }}</p>
                                        <p class="mt-2 text-sm text-gray-600">Status: {{ $opportunity->status }}</p>
                                    </div>
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-100">Score {{ $opportunity->computedScore() ?? $opportunity->score ?? '—' }}</span>
                                </div>

                                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                                    <div class="rounded-lg bg-white p-3 ring-1 ring-inset ring-indigo-100 sm:col-span-2">
                                        <dt class="font-medium text-gray-500">Focus reason</dt>
                                        <dd class="mt-1 text-gray-900">{{ $opportunity->focus_reason ?? 'No focus reason recorded.' }}</dd>
                                    </div>
                                    <div class="rounded-lg bg-white p-3 ring-1 ring-inset ring-indigo-100 sm:col-span-2">
                                        <dt class="font-medium text-gray-500">Next action</dt>
                                        <dd class="mt-1 text-gray-900">
                                            @if ($nextAction)
                                                {{ $nextAction->title }}
                                                <span class="text-gray-500">· Due {{ $nextAction->due_date?->toFormattedDateString() ?? 'not scheduled' }}</span>
                                            @else
                                                No open next action.
                                            @endif
                                        </dd>
                                    </div>
                                    <div class="rounded-lg bg-white p-3 ring-1 ring-inset ring-indigo-100">
                                        <dt class="font-medium text-gray-500">Open gaps</dt>
                                        <dd class="mt-1 text-lg font-semibold text-gray-900">{{ $opportunity->open_gaps_count }}</dd>
                                    </div>
                                    <div class="rounded-lg bg-white p-3 ring-1 ring-inset ring-indigo-100">
                                        <dt class="font-medium text-gray-500">Overdue actions</dt>
                                        <dd class="mt-1 text-lg font-semibold text-gray-900">{{ $opportunity->overdue_actions_count }}</dd>
                                    </div>
                                </dl>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="rounded-2xl border border-green-100 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Completed Actions This Week</h3>
                <p class="mt-1 text-sm text-gray-500">Progress made during the current calendar week.</p>

                @if ($completedActionsThisWeek->isEmpty())
                    <div class="mt-5 rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">No actions have been completed this week.</div>
                @else
                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Action</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Opportunity</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Completed</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($completedActionsThisWeek as $action)
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $action->title }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ $action->opportunity?->title ?? 'No opportunity linked' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ $action->completed_at?->toFormattedDateString() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="rounded-2xl border border-red-100 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Overdue Actions</h3>
                <p class="mt-1 text-sm text-gray-500">Incomplete actions with due dates before today.</p>

                @if ($overdueActions->isEmpty())
                    <div class="mt-5 rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">No incomplete actions are overdue.</div>
                @else
                    <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($overdueActions as $action)
                            <article class="rounded-xl border border-red-100 bg-red-50 p-4">
                                <p class="font-semibold text-red-950">{{ $action->title }}</p>
                                <p class="mt-1 text-sm text-red-800">{{ $action->opportunity?->title ?? 'No opportunity linked' }}</p>
                                <p class="mt-3 text-sm font-medium text-red-900">Due {{ $action->due_date?->toFormattedDateString() }}</p>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="rounded-2xl border border-amber-100 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Open High-Priority Gaps</h3>
                <p class="mt-1 text-sm text-gray-500">Critical or high gaps that still need attention.</p>

                @if ($openHighPriorityGaps->isEmpty())
                    <div class="mt-5 rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">No open critical or high gaps.</div>
                @else
                    <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($openHighPriorityGaps as $gap)
                            <article class="rounded-xl border border-amber-100 bg-amber-50 p-4">
                                <p class="font-semibold text-amber-950">{{ $gap->title }}</p>
                                <p class="mt-1 text-sm text-amber-800">{{ $gap->opportunity->title }}</p>
                                <p class="mt-3 text-sm font-medium text-amber-900">{{ $gap->priority }} · {{ $gap->status }}</p>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="rounded-2xl border border-sky-100 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Contact Follow-Ups Due</h3>
                <p class="mt-1 text-sm text-gray-500">Contact interactions with a follow-up due today or earlier.</p>

                @if ($contactFollowUpsDue->isEmpty())
                    <div class="mt-5 rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">No contact follow-ups are due.</div>
                @else
                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Contact</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Opportunity</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Summary</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Follow up</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($contactFollowUpsDue as $interaction)
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $interaction->contact->name }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ $interaction->opportunity?->title ?? 'No opportunity linked' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ $interaction->summary }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ $interaction->next_follow_up_date?->toFormattedDateString() }}</td>
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
