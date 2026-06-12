<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-indigo-600">Theme</p>
                <h2 class="text-2xl font-semibold leading-tight text-gray-900">{{ $theme->name }}</h2>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('themes.edit', $theme) }}" class="inline-flex items-center justify-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 transition hover:bg-gray-50">Edit</a>
                <a href="{{ route('themes.index') }}" class="text-sm font-semibold text-gray-600 hover:text-gray-900">Back to themes</a>
            </div>
        </div>
    </x-slot>

    <div class="bg-gray-50 py-10">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-md bg-green-50 p-4 text-sm font-medium text-green-700 ring-1 ring-inset ring-green-200">{{ session('status') }}</div>
            @endif

            <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <dl class="grid gap-6 sm:grid-cols-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1 text-base font-semibold text-gray-900">{{ $theme->active ? 'Active' : 'Inactive' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Priority</dt>
                        <dd class="mt-1 text-base font-semibold text-gray-900">{{ $theme->priority ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Linked Opportunities</dt>
                        <dd class="mt-1 text-base font-semibold text-gray-900">{{ $theme->opportunities->count() }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Focus Opportunities</dt>
                        <dd class="mt-1 text-base font-semibold text-gray-900">{{ $portfolio['focus_opportunity_count'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Linked Projects</dt>
                        <dd class="mt-1 text-base font-semibold text-gray-900">{{ $theme->projects->count() }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Linked Objectives</dt>
                        <dd class="mt-1 text-base font-semibold text-gray-900">{{ $theme->strategicObjectives->count() }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Won / Lost / Abandoned</dt>
                        <dd class="mt-1 text-base font-semibold text-gray-900">{{ $portfolio['won_count'] }} / {{ $portfolio['lost_count'] }} / {{ $portfolio['abandoned_count'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Average Score</dt>
                        <dd class="mt-1 text-base font-semibold text-gray-900">{{ $portfolio['average_score'] ?? '—' }}</dd>
                    </div>
                    <div class="sm:col-span-4">
                        <dt class="text-sm font-medium text-gray-500">Description</dt>
                        <dd class="mt-2 whitespace-pre-line rounded-xl bg-gray-50 p-4 text-sm leading-6 text-gray-700 ring-1 ring-inset ring-gray-100">{{ $theme->description ?: 'No description yet.' }}</dd>
                    </div>
                </dl>
            </section>

            <div class="mt-8 grid gap-8 lg:grid-cols-3">
                <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm lg:col-span-3">
                    <h3 class="text-lg font-semibold text-gray-900">Linked Opportunities</h3>
                    @if ($theme->opportunities->isEmpty())
                        <p class="mt-5 rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">No opportunities linked to this theme yet.</p>
                    @else
                        <div class="mt-5 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Opportunity</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Focus</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Outcome</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Score</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @foreach ($theme->opportunities as $opportunity)
                                        <tr>
                                            <td class="px-4 py-4 text-sm font-semibold"><a href="{{ route('opportunities.show', $opportunity) }}" class="text-indigo-600 hover:text-indigo-900">{{ $opportunity->title }}</a><div class="mt-1 font-normal text-gray-500">{{ $opportunity->company ?? '—' }}</div></td>
                                            <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">{{ $opportunity->status }}</td>
                                            <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">{{ $opportunity->is_focus ? 'Focus' : '—' }}</td>
                                            <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">{{ $opportunity->outcome ?? '—' }}</td>
                                            <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">{{ $opportunity->computedScore() ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>

                <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">Linked Projects</h3>
                    <div class="mt-5 space-y-3">
                        @forelse ($theme->projects as $project)
                            <a href="{{ route('projects.show', $project) }}" class="block rounded-xl bg-gray-50 p-4 text-sm font-semibold text-indigo-600 ring-1 ring-inset ring-gray-100 hover:text-indigo-900">{{ $project->name }}<span class="mt-1 block font-normal text-gray-500">{{ $project->status }}</span></a>
                        @empty
                            <p class="rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">No projects linked yet.</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm lg:col-span-2">
                    <h3 class="text-lg font-semibold text-gray-900">Linked Strategic Objectives</h3>
                    <div class="mt-5 space-y-3">
                        @forelse ($theme->strategicObjectives as $strategicObjective)
                            <a href="{{ route('strategic-objectives.show', $strategicObjective) }}" class="block rounded-xl bg-gray-50 p-4 text-sm font-semibold text-indigo-600 ring-1 ring-inset ring-gray-100 hover:text-indigo-900">{{ $strategicObjective->name }}<span class="mt-1 block font-normal text-gray-500">Priority {{ $strategicObjective->priority }} · {{ $strategicObjective->active ? 'Active' : 'Inactive' }}</span></a>
                        @empty
                            <p class="rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">No strategic objectives linked yet.</p>
                        @endforelse
                    </div>
                </section>
            </div>

            <form method="POST" action="{{ route('themes.destroy', $theme) }}" class="mt-8">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-sm font-semibold text-red-600 hover:text-red-900">Delete theme</button>
            </form>
        </div>
    </div>
</x-app-layout>
