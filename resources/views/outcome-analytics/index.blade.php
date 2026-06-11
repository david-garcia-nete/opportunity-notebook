<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-indigo-600">Outcome Analytics</p>
                <h2 class="text-2xl font-semibold leading-tight text-gray-900">Which efforts are producing results?</h2>
            </div>
            <a href="{{ route('opportunities.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-900">Back to Opportunities</a>
        </div>
    </x-slot>

    <div class="bg-gray-50 py-10">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <section data-testid="outcome-summary" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <article class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Total with outcomes</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ $summary['total_with_outcomes'] }}</p>
                </article>
                @foreach ($outcomes as $outcome)
                    <article class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <p class="text-sm font-medium text-gray-500">{{ $outcome }}</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">{{ $summary['counts'][$outcome] }}</p>
                    </article>
                @endforeach
                <article class="rounded-2xl border border-indigo-100 bg-indigo-50 p-5 shadow-sm">
                    <p class="text-sm font-medium text-indigo-700">Win rate</p>
                    <p class="mt-2 text-3xl font-bold text-indigo-900">{{ number_format($summary['win_rate'], 1) }}%</p>
                    <p class="mt-1 text-xs text-indigo-700">Parked opportunities are excluded.</p>
                </article>
            </section>

            <section data-testid="outcome-breakdowns" class="space-y-6">
                @foreach ($breakdowns as $title => $rows)
                    <article class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900">Breakdown by {{ $title }}</h3>
                        @if ($rows->isEmpty())
                            <p class="mt-4 rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">No outcomes have been recorded yet.</p>
                        @else
                            <div class="mt-5 overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $title }}</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Total</th>
                                            @foreach ($outcomes as $outcome)
                                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $outcome }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 bg-white">
                                        @foreach ($rows as $row)
                                            <tr>
                                                <td class="whitespace-nowrap px-4 py-4 text-sm font-semibold text-gray-900">{{ $row['label'] }}</td>
                                                <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $row['total'] }}</td>
                                                @foreach ($outcomes as $outcome)
                                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $row['counts'][$outcome] }}</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </article>
                @endforeach
            </section>

            <section data-testid="outcome-lessons" class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Outcome Lessons</h3>
                <p class="mt-1 text-sm text-gray-500">Recent opportunities with outcomes and the context that may explain them.</p>

                @if ($recentOutcomes->isEmpty())
                    <p class="mt-5 rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">Record outcomes on opportunities to build lessons here.</p>
                @else
                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Title</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Company</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Outcome</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Outcome Date</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Weighted Score</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Readiness Score</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Notes</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($recentOutcomes as $opportunity)
                                    <tr>
                                        <td class="px-4 py-4 text-sm font-semibold">
                                            <a href="{{ route('opportunities.show', $opportunity) }}" class="text-indigo-600 hover:text-indigo-900">{{ $opportunity->title }}</a>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $opportunity->company ?? '—' }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm font-semibold text-gray-900">{{ $opportunity->outcome }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $opportunity->outcome_date?->format('M j, Y') ?? '—' }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $opportunity->weightedScore($preference) ?? '—' }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $readiness->score($opportunity) }}</td>
                                        <td class="min-w-64 px-4 py-4 text-sm text-gray-600">{{ $opportunity->outcome_notes ? \Illuminate\Support\Str::limit($opportunity->outcome_notes, 100) : '—' }}</td>
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
