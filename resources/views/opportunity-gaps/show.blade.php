<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-indigo-600">Opportunity Gap</p>
                <h2 class="text-2xl font-semibold leading-tight text-gray-900">{{ $gap->title }}</h2>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('opportunities.show', $opportunity) }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Back</a>
                <a href="{{ route('actions.create', ['opportunity_gap_id' => $gap->id]) }}" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Create Action</a>
            </div>
        </div>
    </x-slot>

    <div class="bg-gray-50 py-10">
        <div class="mx-auto max-w-4xl space-y-8 px-4 sm:px-6 lg:px-8">
            <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <dl class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Related Opportunity</dt>
                        <dd class="mt-1 text-base font-semibold text-gray-900"><a href="{{ route('opportunities.show', $opportunity) }}" class="text-indigo-600 hover:text-indigo-900">{{ $opportunity->title }}</a></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Priority</dt>
                        <dd class="mt-1 text-base font-semibold text-gray-900">{{ $gap->priority }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Category</dt>
                        <dd class="mt-1 text-base font-semibold text-gray-900">{{ $gap->category }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1 text-base font-semibold text-gray-900">{{ $gap->status }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Description</dt>
                        <dd class="mt-2 whitespace-pre-line rounded-xl bg-gray-50 p-4 text-sm leading-6 text-gray-700 ring-1 ring-inset ring-gray-100">{{ $gap->description ?: 'No description yet.' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Gap Execution</h3>
                        <p class="mt-1 text-sm text-gray-500">Actions linked directly to this gap.</p>
                    </div>
                    <a href="{{ route('actions.create', ['opportunity_gap_id' => $gap->id]) }}" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Create Action</a>
                </div>

                <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-xl bg-amber-50 p-4 ring-1 ring-inset ring-amber-100">
                        <dt class="text-sm font-medium text-amber-700">Open Actions Linked To This Gap</dt>
                        <dd class="mt-1 text-3xl font-bold text-amber-900">{{ $gap->actions->whereNull('completed_at')->count() }}</dd>
                    </div>
                    <div class="rounded-xl bg-green-50 p-4 ring-1 ring-inset ring-green-100">
                        <dt class="text-sm font-medium text-green-700">Completed Actions Linked To This Gap</dt>
                        <dd class="mt-1 text-3xl font-bold text-green-900">{{ $gap->actions->whereNotNull('completed_at')->count() }}</dd>
                    </div>
                </dl>

                @if ($gap->actions->isEmpty())
                    <div class="mt-5 rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">No actions are linked to this gap yet.</div>
                @else
                    <div class="mt-5 space-y-3">
                        @foreach ($gap->actions as $action)
                            <article class="rounded-xl bg-gray-50 p-4 ring-1 ring-inset ring-gray-100">
                                <a href="{{ route('actions.show', $action) }}" class="font-semibold text-indigo-600 hover:text-indigo-900">{{ $action->title }}</a>
                                <p class="mt-1 text-sm text-gray-600">{{ $action->status() }} · Due {{ $action->due_date?->toFormattedDateString() ?? 'not set' }}</p>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
