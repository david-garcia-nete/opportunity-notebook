<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-indigo-600">Actions</p>
                <h2 class="text-2xl font-semibold leading-tight text-gray-900">
                    {{ __('Action Tracking') }}
                </h2>
            </div>
            <a href="{{ route('actions.create') }}" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                New Action
            </a>
        </div>
    </x-slot>

    <div class="bg-gray-50 py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-md bg-green-50 p-4 text-sm font-medium text-green-700 ring-1 ring-inset ring-green-200">
                    {{ session('status') }}
                </div>
            @endif

            <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                @if ($actions->isEmpty())
                    <div class="p-10 text-center">
                        <h3 class="text-lg font-semibold text-gray-900">No actions yet</h3>
                        <p class="mt-2 text-sm text-gray-500">Create the next task that will move an opportunity forward.</p>
                        <a href="{{ route('actions.create') }}" class="mt-6 inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            New Action
                        </a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Title</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Opportunity</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Due Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($actions as $action)
                                    <tr>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-gray-900">{{ $action->title }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                            @if ($action->opportunity)
                                                <a href="{{ route('opportunities.show', $action->opportunity) }}" class="text-indigo-600 hover:text-indigo-900">{{ $action->opportunity->title }}</a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">{{ $action->due_date?->toFormattedDateString() ?? '—' }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">{{ $action->status() }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                            <div class="flex items-center justify-end gap-3">
                                                <a href="{{ route('actions.show', $action) }}" class="text-indigo-600 hover:text-indigo-900">View</a>
                                                <a href="{{ route('actions.edit', $action) }}" class="text-gray-600 hover:text-gray-900">Edit</a>
                                                <form method="POST" action="{{ route('actions.destroy', $action) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
