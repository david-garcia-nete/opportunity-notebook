<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-indigo-600">Review</p>
                <h2 class="text-2xl font-semibold leading-tight text-gray-900">
                    {{ $review->reviewTypeLabel() }} Review
                </h2>
            </div>
            <a href="{{ route('reviews.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Back to Reviews
            </a>
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
                        <dt class="text-sm font-medium text-gray-500">Review Type</dt>
                        <dd class="mt-1 text-base font-semibold text-gray-900">{{ $review->reviewTypeLabel() }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Completed Date</dt>
                        <dd class="mt-1 text-base font-semibold text-gray-900">{{ $review->completed_at?->format('M j, Y g:i A') ?? '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Summary</dt>
                        <dd class="mt-2 whitespace-pre-line rounded-xl bg-gray-50 p-4 text-sm leading-6 text-gray-700 ring-1 ring-inset ring-gray-100">{{ $review->summary ?: 'No summary recorded.' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Notes</dt>
                        <dd class="mt-2 whitespace-pre-line rounded-xl bg-gray-50 p-4 text-sm leading-6 text-gray-700 ring-1 ring-inset ring-gray-100">{{ $review->notes ?: 'No notes recorded.' }}</dd>
                    </div>
                </dl>
            </div>

            <section class="mt-8 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Linked Decisions</p>
                    <h3 class="mt-1 text-lg font-semibold text-gray-900">Opportunity decisions from this review</h3>
                </div>

                @if ($review->opportunityDecisions->isEmpty())
                    <div class="mt-6 rounded-xl bg-slate-50 p-5 text-sm text-gray-600 ring-1 ring-inset ring-slate-100">
                        No opportunity decisions are linked to this review yet.
                    </div>
                @else
                    <div class="mt-6 space-y-4">
                        @foreach ($review->opportunityDecisions as $decision)
                            <article class="rounded-xl bg-slate-50 p-5 ring-1 ring-inset ring-slate-100">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <p class="text-base font-semibold text-gray-900">
                                            <a href="{{ route('opportunities.show', $decision->opportunity) }}" class="text-indigo-700 hover:text-indigo-900">
                                                {{ $decision->opportunity->title }}
                                            </a>
                                        </p>
                                        <p class="mt-1 text-sm text-gray-600">{{ $decision->decisionTypeLabel() }} decision: {{ $decision->reasonCategoryLabel() }}</p>
                                    </div>
                                    <p class="text-sm text-gray-500">{{ $decision->decided_at?->format('M j, Y g:i A') }}</p>
                                </div>
                                @if ($decision->notes)
                                    <p class="mt-3 whitespace-pre-line text-sm leading-6 text-gray-700">{{ $decision->notes }}</p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
