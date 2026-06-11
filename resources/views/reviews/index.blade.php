<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-indigo-600">Reviews</p>
                <h2 class="text-2xl font-semibold leading-tight text-gray-900">
                    {{ __('Review Sessions') }}
                </h2>
            </div>
            <a href="{{ route('reviews.create') }}" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                New Review
            </a>
        </div>
    </x-slot>

    <div class="bg-gray-50 py-10">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-md bg-green-50 p-4 text-sm font-medium text-green-700 ring-1 ring-inset ring-green-200">
                    {{ session('status') }}
                </div>
            @endif

            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Recorded reviews</h3>
                        <p class="mt-1 text-sm text-gray-500">Completed daily, weekly, focus, and portfolio reviews, newest first.</p>
                    </div>
                </div>

                @if ($reviews->isEmpty())
                    <div class="mt-6 rounded-xl bg-slate-50 p-6 text-sm text-gray-600 ring-1 ring-inset ring-slate-100">
                        No reviews recorded yet.
                    </div>
                @else
                    <div class="mt-6 overflow-hidden rounded-xl ring-1 ring-gray-100">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Type</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Completed</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Summary</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Decisions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($reviews as $review)
                                    <tr>
                                        <td class="px-4 py-4 text-sm font-semibold text-gray-900">
                                            <a href="{{ route('reviews.show', $review) }}" class="text-indigo-700 hover:text-indigo-900">
                                                {{ $review->reviewTypeLabel() }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-4 text-sm text-gray-600">{{ $review->completed_at?->format('M j, Y g:i A') ?? '—' }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-600">{{ $review->summary ?: 'No summary recorded.' }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-600">{{ $review->opportunity_decisions_count ?? $review->opportunityDecisions()->count() }}</td>
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
