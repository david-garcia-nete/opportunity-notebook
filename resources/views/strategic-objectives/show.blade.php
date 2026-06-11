<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-indigo-600">Strategic Objective</p>
                <h2 class="text-2xl font-semibold leading-tight text-gray-900">{{ $strategicObjective->name }}</h2>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('strategic-objectives.edit', $strategicObjective) }}" class="inline-flex items-center justify-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 transition hover:bg-gray-50">Edit</a>
                <a href="{{ route('strategic-objectives.index') }}" class="text-sm font-semibold text-gray-600 hover:text-gray-900">Back to objectives</a>
            </div>
        </div>
    </x-slot>

    <div class="bg-gray-50 py-10">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-md bg-green-50 p-4 text-sm font-medium text-green-700 ring-1 ring-inset ring-green-200">{{ session('status') }}</div>
            @endif

            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <dl class="grid gap-6 sm:grid-cols-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Priority</dt>
                        <dd class="mt-1 text-base font-semibold text-gray-900">{{ $strategicObjective->priority }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1 text-base font-semibold text-gray-900">{{ $strategicObjective->active ? 'Active' : 'Inactive' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Linked Opportunities</dt>
                        <dd class="mt-1 text-base font-semibold text-gray-900">{{ $linkedOpportunities->count() }}</dd>
                    </div>
                    <div class="sm:col-span-3">
                        <dt class="text-sm font-medium text-gray-500">Description</dt>
                        <dd class="mt-2 whitespace-pre-line rounded-xl bg-gray-50 p-4 text-sm leading-6 text-gray-700 ring-1 ring-inset ring-gray-100">{{ $strategicObjective->description ?: 'No description yet.' }}</dd>
                    </div>
                </dl>
            </div>

            <section class="mt-8 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Linked Opportunities</h3>
                <p class="mt-1 text-sm text-gray-500">Opportunities supporting this outcome, sorted by computed score.</p>

                @if ($linkedOpportunities->isEmpty())
                    <div class="mt-5 rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">No opportunities linked to this objective yet.</div>
                @else
                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Opportunity</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Score</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Open Gaps</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Next Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($linkedOpportunities as $opportunity)
                                    @php($nextAction = $opportunity->nextAction())
                                    <tr>
                                        <td class="px-4 py-4 text-sm font-semibold"><a href="{{ route('opportunities.show', $opportunity) }}" class="text-indigo-600 hover:text-indigo-900">{{ $opportunity->title }}</a><div class="mt-1 font-normal text-gray-500">{{ $opportunity->company ?? '—' }}</div></td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $opportunity->computedScore() ?? '—' }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $opportunity->status }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm font-semibold text-gray-900">{{ $opportunity->opportunityGaps->where('status', \App\Support\Statuses::GAP_OPEN)->count() }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-600">
                                            @if ($nextAction)
                                                <a href="{{ route('actions.show', $nextAction) }}" class="font-medium text-indigo-600 hover:text-indigo-900">{{ $nextAction->title }}</a>
                                            @elseif ($opportunity->missingNextAction())
                                                <span class="font-medium text-amber-700">Missing next action</span>
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

            <form method="POST" action="{{ route('strategic-objectives.destroy', $strategicObjective) }}" class="mt-8">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-sm font-semibold text-red-600 hover:text-red-900">Delete objective</button>
            </form>
        </div>
    </div>
</x-app-layout>
