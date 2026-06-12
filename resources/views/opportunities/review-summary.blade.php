<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-indigo-600">Opportunity Review Summary</p>
                <h2 class="text-2xl font-semibold leading-tight text-gray-900">
                    {{ $opportunity->title }}
                </h2>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('opportunities.show', $opportunity) }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Back to Opportunity
                </a>
                <a href="{{ route('opportunities.strategic-context', $opportunity) }}" class="inline-flex items-center justify-center rounded-md border border-indigo-200 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 shadow-sm transition hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Strategic Context
                </a>
            </div>
        </div>
    </x-slot>

    <div class="bg-gray-50 py-10">
        <div class="mx-auto max-w-4xl space-y-8 px-4 sm:px-6 lg:px-8">
            <section class="rounded-2xl border border-indigo-100 bg-white p-6 shadow-sm">
                <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Headline</p>
                <h3 class="mt-2 text-2xl font-semibold text-gray-950">{{ $summary['headline'] }}</h3>
            </section>

            <div class="grid gap-8 lg:grid-cols-2">
                <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">Strengths</h3>
                    <ul class="mt-4 space-y-3 text-sm leading-6 text-gray-700">
                        @foreach ($summary['strengths'] as $strength)
                            <li class="rounded-xl bg-green-50 p-3 ring-1 ring-inset ring-green-100">{{ $strength }}</li>
                        @endforeach
                    </ul>
                </section>

                <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">Risks</h3>
                    <ul class="mt-4 space-y-3 text-sm leading-6 text-gray-700">
                        @foreach ($summary['risks'] as $risk)
                            <li class="rounded-xl bg-amber-50 p-3 ring-1 ring-inset ring-amber-100">{{ $risk }}</li>
                        @endforeach
                    </ul>
                </section>
            </div>

            <div class="grid gap-8 lg:grid-cols-2">
                <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">Blockers</h3>
                    <ul class="mt-4 space-y-3 text-sm leading-6 text-gray-700">
                        @foreach ($summary['blockers'] as $blocker)
                            <li class="rounded-xl bg-red-50 p-3 ring-1 ring-inset ring-red-100">{{ $blocker }}</li>
                        @endforeach
                    </ul>
                </section>

                <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Progress</h3>
                    <ul class="mt-4 space-y-3 text-sm leading-6 text-gray-700">
                        @foreach ($summary['recent_progress'] as $progress)
                            <li class="rounded-xl bg-slate-50 p-3 ring-1 ring-inset ring-slate-100">{{ $progress }}</li>
                        @endforeach
                    </ul>
                </section>
            </div>

            <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Decision Prompt</h3>
                <p class="mt-3 rounded-xl bg-indigo-50 p-4 text-sm leading-6 text-indigo-950 ring-1 ring-inset ring-indigo-100">{{ $summary['decision_prompt'] }}</p>
            </section>

            <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Suggested Next Action</h3>
                <p class="mt-3 rounded-xl bg-white p-4 text-sm font-semibold leading-6 text-gray-900 ring-1 ring-inset ring-gray-200">{{ $summary['suggested_next_action'] }}</p>
            </section>
        </div>
    </div>
</x-app-layout>
