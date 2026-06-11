<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-indigo-600">Forecasts</p>
                <h2 class="text-2xl font-semibold leading-tight text-gray-900">{{ __('Opportunity Forecasts') }}</h2>
            </div>
            <p class="text-sm text-gray-500">Transparent, rules-based forecasting — no AI or prediction model.</p>
        </div>
    </x-slot>

    <div class="bg-gray-50 py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <section data-testid="forecast-page" class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Forecast Rankings</h3>
                        <p class="mt-1 text-sm text-gray-500">Forecast score combines weighted opportunity score, readiness, and execution health.</p>
                    </div>
                    <a href="{{ route('forecasts', ['sort' => 'forecast_score']) }}" class="inline-flex items-center justify-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Sort by Forecast Score
                    </a>
                </div>

                @if ($forecastSummaries->isEmpty())
                    <div class="mt-5 rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">
                        Add active opportunities with evaluation fields to see forecast rankings.
                    </div>
                @else
                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Opportunity</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Weighted Score</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Readiness Score</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Execution Health</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Forecast Score</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Forecast Status</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Focus Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($forecastSummaries as $summary)
                                    <tr>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm font-semibold">
                                            <a href="{{ route('opportunities.show', $summary['opportunity']) }}" class="text-indigo-600 hover:text-indigo-900">
                                                {{ $summary['opportunity']->title }}
                                            </a>
                                            <p class="mt-1 text-xs font-normal text-gray-500">{{ $summary['opportunity']->company ?? 'No company listed' }}</p>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">{{ $summary['weighted_score'] ?? '—' }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">{{ $summary['readiness_score'] }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">{{ $summary['execution_health'] }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm font-semibold text-gray-900">{{ $summary['forecast_score'] }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm">
                                            <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-100">{{ $summary['forecast_status'] }}</span>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">{{ $summary['focus_status'] }}</td>
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
