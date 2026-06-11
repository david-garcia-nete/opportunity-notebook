<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-indigo-600">Portfolio</p>
                <h2 class="text-2xl font-semibold leading-tight text-gray-900">Portfolio Readiness</h2>
            </div>
            <p class="text-sm text-gray-500">Am I prepared to win?</p>
        </div>
    </x-slot>

    <div class="bg-gray-50 py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Active Opportunities</p>
                        <h3 class="mt-1 text-lg font-semibold text-gray-900">Readiness by opportunity</h3>
                        <p class="mt-1 text-sm text-gray-500">Readiness is separate from opportunity score: it measures evidence, open gaps, and portfolio support.</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('portfolio-readiness', ['sort' => 'weighted_score', 'direction' => $sort === 'weighted_score' && $direction === 'desc' ? 'asc' : 'desc']) }}" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Sort by Weighted Score</a>
                        <a href="{{ route('portfolio-readiness', ['sort' => 'readiness_score', 'direction' => $sort === 'readiness_score' && $direction === 'desc' ? 'asc' : 'desc']) }}" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Sort by Readiness Score</a>
                    </div>
                </div>

                @if ($opportunities->isEmpty())
                    <div class="mt-6 rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">No active opportunities to assess yet.</div>
                @else
                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Opportunity</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Weighted Score</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Readiness Score</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Readiness Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Open Gaps</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Projects</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Applications</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Focus Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($opportunities as $row)
                                    @php($opportunity = $row['opportunity'])
                                    @php($readiness = $row['readiness'])
                                    <tr class="{{ $readiness['is_low_readiness'] && $opportunity->is_focus ? 'bg-red-50' : '' }}">
                                        <td class="whitespace-nowrap px-4 py-4 text-sm font-semibold text-gray-900">
                                            <a href="{{ route('opportunities.show', $opportunity) }}" class="text-indigo-600 hover:text-indigo-900">{{ $opportunity->title }}</a>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $row['weighted_score'] ?? '—' }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm font-semibold text-gray-900">{{ $readiness['score'] }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $readiness['status'] }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $readiness['open_gaps_count'] }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $readiness['projects_count'] }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $readiness['applications_count'] }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $opportunity->is_focus ? 'Focus' : 'Not Focus' }}</td>
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
