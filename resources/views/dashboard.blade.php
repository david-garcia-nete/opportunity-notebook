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
            ['label' => 'Actions Due Today', 'value' => 0, 'description' => 'Follow-ups and next steps'],
            ['label' => 'Applications This Week', 'value' => 0, 'description' => 'Submitted in the last 7 days'],
        ];

        $pipeline = [
            ['label' => 'Opportunities', 'count' => $opportunityCount],
            ['label' => 'Contacts', 'count' => 0],
            ['label' => 'Actions', 'count' => 0],
            ['label' => 'Applications', 'count' => 0],
            ['label' => 'Projects', 'count' => 0],
        ];
    @endphp

    <div class="bg-gray-50 py-10">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <section class="rounded-3xl bg-gradient-to-br from-indigo-600 via-indigo-700 to-slate-900 p-8 text-white shadow-xl">
                <div class="max-w-3xl">
                    <p class="text-sm font-semibold uppercase tracking-wide text-indigo-100">Today's command center</p>
                    <h1 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">What should I do today?</h1>
                    <p class="mt-4 text-base leading-7 text-indigo-100">
                        Focus on the opportunities, actions, interviews, and projects most likely to create income momentum.
                    </p>
                </div>
            </section>

            <section aria-label="Dashboard metrics" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
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
                            <h3 class="text-lg font-semibold text-gray-900">Pipeline</h3>
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

                <section class="rounded-2xl border border-indigo-100 bg-white p-6 shadow-sm">
                    <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Today's Focus</p>
                    <div class="mt-4 rounded-2xl bg-indigo-50 p-6 text-center ring-1 ring-inset ring-indigo-100">
                        <h3 class="text-xl font-semibold text-gray-900">Generate Today's Plan</h3>
                        <p class="mt-2 text-sm font-medium text-indigo-700">Coming Soon</p>
                    </div>
                </section>
            </div>

            <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Recent Activity</h3>
                <div class="mt-5 rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">
                    Activity will appear here as tracked entities are added.
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
