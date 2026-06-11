<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-indigo-600">Strategy</p>
                <h2 class="text-2xl font-semibold leading-tight text-gray-900">Strategic Objectives</h2>
            </div>
            <a href="{{ route('strategic-objectives.create') }}" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">New Objective</a>
        </div>
    </x-slot>

    <div class="bg-gray-50 py-10">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-md bg-green-50 p-4 text-sm font-medium text-green-700 ring-1 ring-inset ring-green-200">{{ session('status') }}</div>
            @endif

            @if ($strategicObjectives->isEmpty())
                <div class="rounded-2xl border border-gray-100 bg-white p-6 text-sm text-gray-500 shadow-sm">No strategic objectives yet.</div>
            @else
                <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Priority</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Linked Opportunities</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($strategicObjectives as $strategicObjective)
                                <tr>
                                    <td class="px-4 py-4 text-sm font-semibold"><a href="{{ route('strategic-objectives.show', $strategicObjective) }}" class="text-indigo-600 hover:text-indigo-900">{{ $strategicObjective->name }}</a></td>
                                    <td class="px-4 py-4 text-sm text-gray-600">{{ $strategicObjective->priority }}</td>
                                    <td class="px-4 py-4 text-sm text-gray-600">{{ $strategicObjective->active ? 'Active' : 'Inactive' }}</td>
                                    <td class="px-4 py-4 text-sm text-gray-600">{{ $strategicObjective->opportunities_count }}</td>
                                    <td class="px-4 py-4 text-right text-sm"><a href="{{ route('strategic-objectives.edit', $strategicObjective) }}" class="font-semibold text-indigo-600 hover:text-indigo-900">Edit</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
