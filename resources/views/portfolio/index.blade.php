<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-indigo-600">Strategic Portfolio</p>
                <h2 class="text-2xl font-semibold leading-tight text-gray-900">{{ __('Portfolio View') }}</h2>
            </div>
            <p class="text-sm text-gray-500">Executive overview of opportunity health, coverage, risk, and effort.</p>
        </div>
    </x-slot>

    <div class="bg-gray-50 py-10">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <section data-testid="portfolio-summary" class="rounded-2xl border border-indigo-100 bg-white p-6 shadow-sm">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Portfolio Summary</p>
                    <h3 class="mt-1 text-lg font-semibold text-gray-900">What is my overall opportunity portfolio?</h3>
                </div>
                <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        ['label' => 'Total Opportunities', 'value' => $metrics['total_opportunities']],
                        ['label' => 'Active Opportunities', 'value' => $metrics['active_opportunities']],
                        ['label' => 'Focused Opportunities', 'value' => $metrics['focused_opportunity_count']],
                        ['label' => 'Forecasted Strong Opportunities', 'value' => $metrics['forecasted_strong_opportunities']],
                        ['label' => 'Forecasted At-Risk Opportunities', 'value' => $metrics['forecasted_at_risk_opportunities']],
                        ['label' => 'Average Opportunity Score', 'value' => number_format($metrics['average_opportunity_score'], 1)],
                        ['label' => 'Average Readiness Score', 'value' => number_format($metrics['average_readiness_score'], 1)],
                        ['label' => 'Average Forecast Score', 'value' => number_format($metrics['average_forecast_score'], 1)],
                    ] as $metric)
                        <div class="rounded-xl bg-gray-50 p-4 ring-1 ring-inset ring-gray-100">
                            <p class="text-sm font-medium text-gray-600">{{ $metric['label'] }}</p>
                            <p class="mt-2 text-3xl font-bold text-gray-900">{{ $metric['value'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <section data-testid="strategic-objective-coverage" class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Strategic Objective Coverage</p>
                    <h3 class="mt-1 text-lg font-semibold text-gray-900">Which objectives have strong support?</h3>
                </div>
                <div class="mt-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Objective</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Priority</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Linked Opportunities</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Focused</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Avg Forecast</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Avg Readiness</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Coverage</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($objectiveCoverage as $row)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm font-semibold text-gray-900">{{ $row['objective']->name }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">{{ $row['priority'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">{{ $row['linked_opportunity_count'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">{{ $row['focused_opportunity_count'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">{{ number_format($row['average_forecast_score'], 1) }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">{{ number_format($row['average_readiness_score'], 1) }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm"><span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-100">{{ $row['coverage'] }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-6 text-sm text-gray-500">Create strategic objectives to see portfolio coverage.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section data-testid="opportunity-distribution" class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Opportunity Distribution</p>
                    <h3 class="mt-1 text-lg font-semibold text-gray-900">Am I over-concentrated in one area?</h3>
                </div>
                <div class="mt-6 grid gap-6 lg:grid-cols-2">
                    @foreach ($distributions as $title => $rows)
                        <div class="overflow-hidden rounded-xl ring-1 ring-gray-100">
                            <h4 class="bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-900">{{ $title }}</h4>
                            <table class="min-w-full divide-y divide-gray-100">
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @forelse ($rows as $row)
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ $row['label'] }}</td>
                                            <td class="px-4 py-3 text-right text-sm font-semibold text-gray-900">{{ $row['count'] }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="2" class="px-4 py-3 text-sm text-gray-500">No data yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                </div>
            </section>

            <section data-testid="focus-portfolio" class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Focus Portfolio</p>
                    <h3 class="mt-1 text-lg font-semibold text-gray-900">Where am I investing my effort?</h3>
                </div>
                <div class="mt-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Opportunity</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Weighted Score</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Readiness Score</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Forecast Score</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Forecast Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Open Actions</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Overdue Actions</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Critical Gaps</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Strategic Objectives</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($focusOpportunities as $summary)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm font-semibold"><a href="{{ route('opportunities.show', $summary['opportunity']) }}" class="text-indigo-600 hover:text-indigo-900">{{ $summary['opportunity']->title }}</a></td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">{{ $summary['weighted_score'] ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">{{ $summary['readiness_score'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm font-semibold text-gray-900">{{ $summary['forecast_score'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">{{ $summary['forecast_status'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">{{ $summary['open_actions_count'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">{{ $summary['overdue_actions_count'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">{{ $summary['critical_gaps_count'] }}</td>
                                    <td class="px-4 py-4 text-sm text-gray-700">{{ $summary['strategic_objectives']->pluck('name')->join(', ') ?: 'No strategic objective' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="px-4 py-6 text-sm text-gray-500">Mark opportunities as focus opportunities to build your focus portfolio.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="grid gap-8 lg:grid-cols-2">
                <section data-testid="portfolio-risks" class="rounded-2xl border border-red-100 bg-white p-6 shadow-sm">
                    <p class="text-sm font-semibold uppercase tracking-wide text-red-600">Portfolio Risks</p>
                    <h3 class="mt-1 text-lg font-semibold text-gray-900">What needs intervention?</h3>
                    <div class="mt-5 space-y-4">
                        @forelse ($portfolioRisks as $summary)
                            <article class="rounded-xl bg-red-50 p-4 ring-1 ring-inset ring-red-100">
                                <a href="{{ route('opportunities.show', $summary['opportunity']) }}" class="font-semibold text-red-800 hover:text-red-950">{{ $summary['opportunity']->title }}</a>
                                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-800">
                                    @foreach ($summary['risk_reasons'] as $reason)
                                        <li>{{ $reason }}</li>
                                    @endforeach
                                </ul>
                            </article>
                        @empty
                            <p class="rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">No focus portfolio risks detected.</p>
                        @endforelse
                    </div>
                </section>

                <section data-testid="portfolio-strengths" class="rounded-2xl border border-emerald-100 bg-white p-6 shadow-sm">
                    <p class="text-sm font-semibold uppercase tracking-wide text-emerald-600">Portfolio Strengths</p>
                    <h3 class="mt-1 text-lg font-semibold text-gray-900">Where am I likely to succeed?</h3>
                    <div class="mt-5 space-y-4">
                        @forelse ($portfolioStrengths as $summary)
                            <article class="rounded-xl bg-emerald-50 p-4 ring-1 ring-inset ring-emerald-100">
                                <a href="{{ route('opportunities.show', $summary['opportunity']) }}" class="font-semibold text-emerald-800 hover:text-emerald-950">{{ $summary['opportunity']->title }}</a>
                                <p class="mt-2 text-sm text-emerald-800">Forecast {{ $summary['forecast_score'] }} · Readiness {{ $summary['readiness_score'] }} · Active execution · No overdue actions</p>
                            </article>
                        @empty
                            <p class="rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">No portfolio strengths detected yet.</p>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
