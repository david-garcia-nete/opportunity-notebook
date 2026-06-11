<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-indigo-600">Unified Timeline</p>
                <h2 class="text-2xl font-semibold leading-tight text-gray-900">Timeline</h2>
            </div>
            <div class="flex gap-3">
                @if ($focusOnly)
                    <a href="{{ route('timeline.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Show All</a>
                @else
                    <a href="{{ route('timeline.index', ['focus' => 1]) }}" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Focus Only</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="bg-gray-50 py-10">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Upcoming</h3>
                        <p class="mt-1 text-sm text-gray-500">Open action due dates and contact follow-ups across {{ $focusOnly ? 'focus opportunities' : 'all opportunities' }}.</p>
                    </div>
                </div>

                @include('timeline.partials.items', ['items' => $timeline['upcoming'], 'emptyMessage' => 'No upcoming timeline items yet.'])
            </section>

            <section class="mt-8 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Recent History</h3>
                <p class="mt-1 text-sm text-gray-500">Completed work, submissions, contact interactions, and gap progress.</p>

                @include('timeline.partials.items', ['items' => $timeline['history'], 'emptyMessage' => 'No historical timeline items yet.'])
            </section>
        </div>
    </div>
</x-app-layout>
