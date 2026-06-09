<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-indigo-600">Projects</p>
                <h2 class="text-2xl font-semibold leading-tight text-gray-900">
                    {{ $project->name }}
                </h2>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('projects.edit', $project) }}" class="inline-flex items-center justify-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 transition hover:bg-gray-50">
                    Edit
                </a>
                <a href="{{ route('projects.index') }}" class="text-sm font-semibold text-gray-600 hover:text-gray-900">Back to projects</a>
            </div>
        </div>
    </x-slot>

    <div class="bg-gray-50 py-10">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-md bg-green-50 p-4 text-sm font-medium text-green-700 ring-1 ring-inset ring-green-200">
                    {{ session('status') }}
                </div>
            @endif

            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <dl class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Name</dt>
                        <dd class="mt-1 text-base font-semibold text-gray-900">{{ $project->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1 text-base font-semibold text-gray-900">{{ $project->status }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">URL</dt>
                        <dd class="mt-1 text-base font-semibold text-gray-900">
                            @if ($project->url)
                                <a href="{{ $project->url }}" class="text-indigo-600 hover:text-indigo-900" target="_blank" rel="noopener noreferrer">{{ $project->url }}</a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Description</dt>
                        <dd class="mt-2 whitespace-pre-line rounded-xl bg-gray-50 p-4 text-sm leading-6 text-gray-700 ring-1 ring-inset ring-gray-100">{{ $project->description ?: 'No description yet.' }}</dd>
                    </div>
                </dl>
            </div>

            <section class="mt-8 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Opportunities</h3>
                    <p class="mt-1 text-sm text-gray-500">Income opportunities supported by this project.</p>
                </div>

                <form method="POST" action="{{ route('projects.opportunities.store', $project) }}" class="mt-5 grid gap-4 rounded-xl bg-gray-50 p-4 ring-1 ring-inset ring-gray-100 sm:grid-cols-2">
                    @csrf
                    <div>
                        <label for="opportunity_id" class="block text-sm font-medium text-gray-700">Opportunity</label>
                        <select id="opportunity_id" name="opportunity_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Choose an opportunity</option>
                            @foreach ($availableOpportunities as $opportunity)
                                <option value="{{ $opportunity->id }}" @selected(old('opportunity_id') == $opportunity->id)>{{ $opportunity->title }}{{ $opportunity->company ? ' — '.$opportunity->company : '' }}</option>
                            @endforeach
                        </select>
                        @error('opportunity_id')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="opportunity_notes" class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea id="opportunity_notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="How this project supports the opportunity">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            Attach Opportunity
                        </button>
                    </div>
                </form>

                @if ($project->opportunities->isEmpty())
                    <div class="mt-5 rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">
                        No opportunities linked to this project yet.
                    </div>
                @else
                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Title</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Company</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Notes</th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($project->opportunities as $opportunity)
                                    <tr>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm font-semibold text-gray-900">
                                            <a href="{{ route('opportunities.show', $opportunity) }}" class="text-indigo-600 hover:text-indigo-900">{{ $opportunity->title }}</a>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $opportunity->company ?? '—' }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $opportunity->status }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-600">{{ $opportunity->pivot->notes ?? '—' }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-right text-sm">
                                            <form method="POST" action="{{ route('projects.opportunities.destroy', [$project, $opportunity]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="font-semibold text-red-600 hover:text-red-900">Remove</button>
                                            </form>
                                        </td>
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
