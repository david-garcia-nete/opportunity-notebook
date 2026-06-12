<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-indigo-600">Strategic Grouping</p>
                <h2 class="text-2xl font-semibold leading-tight text-gray-900">Themes</h2>
            </div>
            <a href="{{ route('themes.create') }}" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">New Theme</a>
        </div>
    </x-slot>

    <div class="bg-gray-50 py-10">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-md bg-green-50 p-4 text-sm font-medium text-green-700 ring-1 ring-inset ring-green-200">{{ session('status') }}</div>
            @endif

            <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Theme Portfolio Analysis</h3>
                <p class="mt-1 text-sm text-gray-500">A lightweight view of which strategic arenas contain focus, outcomes, and scored opportunities.</p>

                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Theme</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Opportunities</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Focus</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Won</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Lost</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Abandoned</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Average Score</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($themePortfolio as $summary)
                                <tr>
                                    <td class="px-4 py-4 text-sm font-semibold"><a href="{{ route('themes.show', $summary['theme']) }}" class="text-indigo-600 hover:text-indigo-900">{{ $summary['theme']->name }}</a></td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">{{ $summary['opportunity_count'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">{{ $summary['focus_opportunity_count'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">{{ $summary['won_count'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">{{ $summary['lost_count'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">{{ $summary['abandoned_count'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">{{ $summary['average_score'] ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-6 text-sm text-gray-500">Create themes to group opportunities, projects, and objectives into strategic arenas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="mt-8 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">All Themes</h3>
                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    @forelse ($themes as $theme)
                        <article class="rounded-xl bg-gray-50 p-4 ring-1 ring-inset ring-gray-100">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <a href="{{ route('themes.show', $theme) }}" class="font-semibold text-indigo-600 hover:text-indigo-900">{{ $theme->name }}</a>
                                    <p class="mt-1 text-sm text-gray-500">{{ $theme->active ? 'Active' : 'Inactive' }}{{ $theme->priority !== null ? ' · Priority '.$theme->priority : '' }}</p>
                                </div>
                                <a href="{{ route('themes.edit', $theme) }}" class="text-sm font-semibold text-gray-600 hover:text-gray-900">Edit</a>
                            </div>
                            <p class="mt-3 text-sm text-gray-700">{{ $theme->description ?: 'No description yet.' }}</p>
                            <dl class="mt-4 grid grid-cols-3 gap-3 text-sm">
                                <div><dt class="text-gray-500">Opportunities</dt><dd class="font-semibold text-gray-900">{{ $theme->opportunities_count }}</dd></div>
                                <div><dt class="text-gray-500">Projects</dt><dd class="font-semibold text-gray-900">{{ $theme->projects_count }}</dd></div>
                                <div><dt class="text-gray-500">Objectives</dt><dd class="font-semibold text-gray-900">{{ $theme->strategic_objectives_count }}</dd></div>
                            </dl>
                        </article>
                    @empty
                        <p class="rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">No themes yet.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
