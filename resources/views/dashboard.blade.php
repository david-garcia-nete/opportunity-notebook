<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-indigo-600">Opportunity Notebook</p>
                <h2 class="text-2xl font-semibold leading-tight text-gray-900">
                    {{ __('Dashboard') }}
                </h2>
            </div>
            <p class="text-sm text-gray-500">Opportunities → Actions → Interviews → Income</p>
        </div>
    </x-slot>

    @php
        $metrics = [
            ['label' => 'Opportunities', 'value' => $opportunityCount, 'description' => 'Total opportunities tracked'],
            ['label' => 'Active Opportunities', 'value' => $activeOpportunityCount, 'description' => 'Worth attention right now'],
            ['label' => 'Actions Due Today', 'value' => $actionsDueTodayCount, 'description' => 'Follow-ups and next steps'],
            ['label' => 'Overdue Actions', 'value' => $overdueActionCount, 'description' => 'Need attention now'],
            ['label' => 'Applications This Week', 'value' => $applicationsThisWeekCount, 'description' => 'Recent submissions made'],
        ];

        $pipeline = [
            ['label' => 'Opportunities', 'count' => $opportunityCount],
            ['label' => 'Contacts', 'count' => $contactCount],
            ['label' => 'Actions', 'count' => $actionCount],
            ['label' => 'Applications', 'count' => $applicationCount],
            ['label' => 'Projects', 'count' => $projectCount],
        ];
    @endphp

    <div class="bg-gray-50 py-10">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <section class="rounded-3xl bg-gradient-to-br from-indigo-600 via-indigo-700 to-slate-900 p-8 text-white shadow-xl">
                <div class="max-w-3xl">
                    <p class="text-sm font-semibold uppercase tracking-wide text-indigo-100">Today's command center</p>
                    <h1 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">What deserves attention today?</h1>
                    <p class="mt-4 text-base leading-7 text-indigo-100">
                        Invest attention where the computed score, stalled next steps, and overdue follow-ups suggest the strongest income momentum.
                    </p>
                </div>
            </section>

            <section aria-label="Dashboard metrics" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                @foreach ($metrics as $metric)
                    <article class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                        <p class="text-sm font-medium text-gray-500">{{ $metric['label'] }}</p>
                        <p class="mt-3 text-4xl font-bold text-gray-900">{{ $metric['value'] }}</p>
                        <p class="mt-2 text-sm text-gray-500">{{ $metric['description'] }}</p>
                    </article>
                @endforeach
            </section>

            <div class="grid gap-8 lg:grid-cols-3">
                <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm lg:col-span-2">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Top Ranked Opportunities</h3>
                            <p class="mt-1 text-sm text-gray-500">Active opportunities sorted by computed opportunity score.</p>
                        </div>
                    </div>

                    @if ($topRankedOpportunities->isEmpty())
                        <div class="mt-5 rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">
                            Add evaluation fields to opportunities to see ranked priorities here.
                        </div>
                    @else
                        <div class="mt-6 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Title</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Company</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Computed Score</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @foreach ($topRankedOpportunities as $opportunity)
                                        <tr>
                                            <td class="whitespace-nowrap px-4 py-4 text-sm font-semibold">
                                                <a href="{{ route('opportunities.show', $opportunity) }}" class="text-indigo-600 hover:text-indigo-900">
                                                    {{ $opportunity->title }}
                                                </a>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $opportunity->company ?? '—' }}</td>
                                            <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $opportunity->status }}</td>
                                            <td class="whitespace-nowrap px-4 py-4 text-sm font-semibold text-gray-900">{{ $opportunity->computedScore() }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>

                <section class="rounded-2xl border border-indigo-100 bg-white p-6 shadow-sm">
                    <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Today's Focus</p>
                    <div class="mt-4 rounded-2xl bg-indigo-50 p-6 text-center ring-1 ring-inset ring-indigo-100">
                        <h3 class="text-xl font-semibold text-gray-900">What needs attention?</h3>
                        @if ($overdueActionCount > 0)
                            <p class="mt-2 text-sm font-medium text-indigo-700">You have overdue actions that need attention.</p>
                        @elseif ($actionsDueTodayCount > 0)
                            <p class="mt-2 text-sm font-medium text-indigo-700">You have actions due today.</p>
                        @else
                            <p class="mt-2 text-sm font-medium text-indigo-700">No urgent actions. Consider creating new opportunities.</p>
                        @endif
                    </div>
                </section>
            </div>

            <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Top Objectives</h3>
                        <p class="mt-1 text-sm text-gray-500">Active outcomes and the opportunities currently supporting them.</p>
                    </div>
                </div>

                @if ($topObjectives->isEmpty())
                    <div class="mt-5 rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">
                        Add active strategic objectives to connect opportunities to outcomes.
                    </div>
                @else
                    <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($topObjectives as $objectiveSummary)
                            <article class="rounded-xl bg-gray-50 p-4 ring-1 ring-inset ring-gray-100">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <a href="{{ route('strategic-objectives.show', $objectiveSummary['objective']) }}" class="font-semibold text-indigo-600 hover:text-indigo-900">{{ $objectiveSummary['objective']->name }}</a>
                                        <p class="mt-1 text-sm text-gray-500">Priority {{ $objectiveSummary['objective']->priority }}</p>
                                    </div>
                                    <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">{{ $objectiveSummary['linked_opportunity_count'] }} linked</span>
                                </div>
                                <dl class="mt-4 space-y-3 text-sm">
                                    <div>
                                        <dt class="font-medium text-gray-500">Highest-ranked linked opportunity</dt>
                                        <dd class="mt-1 font-semibold text-gray-900">
                                            @if ($objectiveSummary['highest_ranked_opportunity'])
                                                <a href="{{ route('opportunities.show', $objectiveSummary['highest_ranked_opportunity']) }}" class="text-indigo-600 hover:text-indigo-900">{{ $objectiveSummary['highest_ranked_opportunity']->title }}</a>
                                            @else
                                                —
                                            @endif
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="font-medium text-gray-500">Average opportunity score</dt>
                                        <dd class="mt-1 font-semibold text-gray-900">{{ $objectiveSummary['average_opportunity_score'] ?? '—' }}</dd>
                                    </div>
                                </dl>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="rounded-2xl border border-amber-100 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">High-Value Opportunities Missing Next Action</h3>
                <p class="mt-1 text-sm text-gray-500">Important opportunities with no incomplete/open action are stalled.</p>

                @if ($highValueOpportunitiesMissingNextAction->isEmpty())
                    <div class="mt-5 rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">
                        No high-value opportunities are missing a next action.
                    </div>
                @else
                    <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($highValueOpportunitiesMissingNextAction as $opportunity)
                            <article class="rounded-xl border border-amber-100 bg-amber-50 p-4">
                                <a href="{{ route('opportunities.show', $opportunity) }}" class="font-semibold text-amber-900 hover:text-amber-700">
                                    {{ $opportunity->title }}
                                </a>
                                <p class="mt-1 text-sm text-amber-800">{{ $opportunity->company ?? 'No company listed' }}</p>
                                <p class="mt-3 text-sm font-medium text-amber-900">Computed score: {{ $opportunity->computedScore() }}</p>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>


            <section class="rounded-2xl border border-red-100 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">High-Value Opportunities With Critical Gaps</h3>
                <p class="mt-1 text-sm text-gray-500">Scored opportunities that look valuable but still have open manual gaps to close.</p>

                @if ($highValueOpportunitiesWithCriticalGaps->isEmpty())
                    <div class="mt-5 rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">
                        No high-value scored opportunities currently have open gaps.
                    </div>
                @else
                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Opportunity</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Score</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Open Gap Count</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Highest Priority Gap</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($highValueOpportunitiesWithCriticalGaps as $gapSummary)
                                    <tr>
                                        <td class="px-4 py-4 text-sm font-semibold">
                                            <a href="{{ route('opportunities.show', $gapSummary['opportunity']) }}" class="text-indigo-600 hover:text-indigo-900">{{ $gapSummary['opportunity']->title }}</a>
                                            <div class="mt-1 font-normal text-gray-500">{{ $gapSummary['opportunity']->company ?? '—' }}</div>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm font-semibold text-gray-900">{{ $gapSummary['opportunity']->computedScore() }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $gapSummary['open_gap_count'] }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-600">
                                            @if ($gapSummary['highest_priority_gap'])
                                                <span class="font-semibold text-gray-900">{{ $gapSummary['highest_priority_gap']->title }}</span>
                                                <span class="ml-2 rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 ring-1 ring-inset ring-red-100">{{ $gapSummary['highest_priority_gap']->priority }}</span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <div class="grid gap-8 lg:grid-cols-2">
                <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">Overdue Actions on High-Value Opportunities</h3>
                    <p class="mt-1 text-sm text-gray-500">Overdue open actions prioritized by related opportunity score.</p>

                    @if ($overdueActionsOnHighValueOpportunities->isEmpty())
                        <div class="mt-5 rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">
                            No overdue actions are attached to scored opportunities.
                        </div>
                    @else
                        <div class="mt-5 space-y-3">
                            @foreach ($overdueActionsOnHighValueOpportunities as $action)
                                <article class="rounded-xl bg-gray-50 p-4 ring-1 ring-inset ring-gray-100">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <a href="{{ route('actions.show', $action) }}" class="font-semibold text-gray-900 hover:text-indigo-700">{{ $action->title }}</a>
                                            <p class="mt-1 text-sm text-gray-500">Due {{ $action->due_date?->toFormattedDateString() ?? '—' }}</p>
                                        </div>
                                        <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">Overdue</span>
                                    </div>
                                    <p class="mt-3 text-sm text-gray-600">
                                        Related opportunity:
                                        <a href="{{ route('opportunities.show', $action->opportunity) }}" class="font-medium text-indigo-600 hover:text-indigo-900">
                                            {{ $action->opportunity->title }}
                                        </a>
                                    </p>
                                    <p class="mt-1 text-sm font-medium text-gray-900">Opportunity score: {{ $action->opportunity->computedScore() }}</p>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </section>

                <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Applications for High-Value Opportunities</h3>
                    <p class="mt-1 text-sm text-gray-500">Recent applications prioritized by related opportunity score.</p>

                    @if ($recentApplicationsForHighValueOpportunities->isEmpty())
                        <div class="mt-5 rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">
                            Recent applications for scored opportunities will appear here.
                        </div>
                    @else
                        <div class="mt-5 space-y-3">
                            @foreach ($recentApplicationsForHighValueOpportunities as $application)
                                <article class="rounded-xl bg-gray-50 p-4 ring-1 ring-inset ring-gray-100">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <a href="{{ route('applications.show', $application) }}" class="font-semibold text-gray-900 hover:text-indigo-700">
                                                {{ ucfirst($application->status) }} application
                                            </a>
                                            <p class="mt-1 text-sm text-gray-500">Applied {{ $application->applied_at?->toFormattedDateString() ?? '—' }}</p>
                                        </div>
                                        <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">Score {{ $application->opportunity->computedScore() }}</span>
                                    </div>
                                    <p class="mt-3 text-sm text-gray-600">
                                        Related opportunity:
                                        <a href="{{ route('opportunities.show', $application->opportunity) }}" class="font-medium text-indigo-600 hover:text-indigo-900">
                                            {{ $application->opportunity->title }}
                                        </a>
                                    </p>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>


            <div class="grid gap-8 lg:grid-cols-2">
                <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">Contacts Requiring Follow-Up</h3>
                    <p class="mt-1 text-sm text-gray-500">Contact interactions with follow-up dates due today or earlier.</p>

                    @if ($contactsRequiringFollowUp->isEmpty())
                        <div class="mt-5 rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">
                            No contact follow-ups are due right now.
                        </div>
                    @else
                        <div class="mt-5 space-y-3">
                            @foreach ($contactsRequiringFollowUp as $interaction)
                                <article class="rounded-xl bg-gray-50 p-4 ring-1 ring-inset ring-gray-100">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <a href="{{ route('contacts.show', $interaction->contact) }}" class="font-semibold text-gray-900 hover:text-indigo-700">{{ $interaction->contact->name }}</a>
                                            <p class="mt-1 text-sm text-gray-500">Follow up by {{ $interaction->next_follow_up_date->toFormattedDateString() }}</p>
                                        </div>
                                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">{{ $interaction->interaction_type }}</span>
                                    </div>
                                    <p class="mt-3 text-sm text-gray-600">{{ $interaction->summary }}</p>
                                    @if ($interaction->opportunity)
                                        <p class="mt-2 text-sm text-gray-600">
                                            Opportunity:
                                            <a href="{{ route('opportunities.show', $interaction->opportunity) }}" class="font-medium text-indigo-600 hover:text-indigo-900">{{ $interaction->opportunity->title }}</a>
                                        </p>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    @endif
                </section>

                <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">Dormant High-Value Relationships</h3>
                    <p class="mt-1 text-sm text-gray-500">Contacts tied to strong opportunities with no interaction in 30+ days.</p>

                    @if ($dormantHighValueRelationships->isEmpty())
                        <div class="mt-5 rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">
                            No high-value relationships are dormant right now.
                        </div>
                    @else
                        <div class="mt-5 space-y-3">
                            @foreach ($dormantHighValueRelationships as $relationship)
                                <article class="rounded-xl bg-gray-50 p-4 ring-1 ring-inset ring-gray-100">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <a href="{{ route('contacts.show', $relationship['contact']) }}" class="font-semibold text-gray-900 hover:text-indigo-700">{{ $relationship['contact']->name }}</a>
                                            <p class="mt-1 text-sm text-gray-500">
                                                Last interaction: {{ $relationship['last_interaction']?->interaction_date->toFormattedDateString() ?? 'No interactions recorded' }}
                                            </p>
                                        </div>
                                        <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">Avg score {{ $relationship['average_opportunity_score'] ?? '—' }}</span>
                                    </div>
                                    <p class="mt-3 text-sm text-gray-600">
                                        Helping with:
                                        @foreach ($relationship['high_value_opportunities']->take(3) as $opportunity)
                                            <a href="{{ route('opportunities.show', $opportunity) }}" class="font-medium text-indigo-600 hover:text-indigo-900">{{ $opportunity->title }}</a>@if (! $loop->last), @endif
                                        @endforeach
                                    </p>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>

            <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Opportunity Pipeline Summary</h3>
                        <p class="mt-1 text-sm text-gray-500">A simple count across the MVP workflow.</p>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    @foreach ($pipeline as $item)
                        <div class="rounded-xl bg-gray-50 p-4 ring-1 ring-inset ring-gray-100">
                            <p class="text-sm font-medium text-gray-600">{{ $item['label'] }}</p>
                            <p class="mt-3 text-3xl font-bold text-indigo-700">{{ $item['count'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
