<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-indigo-600">Daily Action Queue</p>
                <h2 class="text-2xl font-semibold leading-tight text-gray-900">
                    {{ __('What should I do today?') }}
                </h2>
            </div>
            <a href="{{ route('opportunities.index', ['focus' => 1]) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-900">Review focus opportunities</a>
        </div>
    </x-slot>

    @php
        $summaryCards = [
            ['label' => 'Focus Opportunities', 'value' => $summary['focus_opportunities_count']],
            ['label' => 'Queue Items', 'value' => $summary['queue_item_count']],
            ['label' => 'Overdue Actions', 'value' => $summary['overdue_action_count']],
            ['label' => 'Due Today Actions', 'value' => $summary['due_today_action_count']],
            ['label' => 'Follow-Ups Due', 'value' => $summary['follow_ups_due_count']],
            ['label' => 'Critical Gaps', 'value' => $summary['critical_gap_count']],
        ];
    @endphp

    <div class="bg-gray-50 py-10">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <section class="rounded-3xl bg-gradient-to-br from-indigo-600 via-indigo-700 to-slate-900 p-8 text-white shadow-xl">
                <div class="max-w-3xl">
                    <p class="text-sm font-semibold uppercase tracking-wide text-indigo-100">Personal work queue</p>
                    <h1 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">Do the highest-value work first.</h1>
                    <p class="mt-4 text-base leading-7 text-indigo-100">
                        This queue is built dynamically from focus opportunities, action due dates, follow-ups, and open gaps so you do not have to search manually.
                    </p>
                </div>
            </section>

            <section aria-label="Daily queue summary" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                @foreach ($summaryCards as $card)
                    <article class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <p class="text-sm font-medium text-gray-500">{{ $card['label'] }}</p>
                        <p class="mt-3 text-3xl font-bold text-gray-900">{{ $card['value'] }}</p>
                    </article>
                @endforeach
            </section>

            <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Today's Queue</h3>
                        <p class="mt-1 text-sm text-gray-500">Prioritized across focused opportunities, follow-ups, and gaps.</p>
                    </div>
                    <span class="inline-flex w-fit rounded-full bg-indigo-100 px-3 py-1 text-sm font-semibold text-indigo-700">{{ $summary['queue_item_count'] }} items</span>
                </div>

                @if ($queueItems->isEmpty())
                    <div class="mt-6 rounded-xl bg-gray-50 p-5 text-sm text-gray-600 ring-1 ring-inset ring-gray-100">
                        You are caught up. Consider reviewing focus opportunities or sourcing new opportunities.
                    </div>
                @else
                    <div class="mt-6 space-y-4">
                        @foreach ($queueItems as $item)
                            <article class="rounded-xl border border-gray-100 bg-gray-50 p-5 ring-1 ring-inset ring-white">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="space-y-3">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold uppercase tracking-wide text-gray-600 ring-1 ring-inset ring-gray-200">{{ $item['type_label'] }}</span>
                                            <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">{{ $item['priority_label'] }}</span>
                                        </div>

                                        <div>
                                            <a href="{{ $item['url'] }}" class="text-lg font-semibold text-indigo-700 hover:text-indigo-900">{{ $item['title'] }}</a>
                                            <p class="mt-1 text-sm text-gray-600">
                                                Related opportunity:
                                                @if ($item['opportunity'])
                                                    <a href="{{ route('opportunities.show', $item['opportunity']) }}" class="font-medium text-gray-900 hover:text-indigo-700">{{ $item['opportunity']->title }}</a>
                                                @else
                                                    No opportunity linked
                                                @endif
                                            </p>
                                        </div>
                                    </div>

                                    @if ($item['due_date'])
                                        <div class="rounded-lg bg-white px-4 py-3 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-200">
                                            Due {{ $item['due_date']->toFormattedDateString() }}
                                        </div>
                                    @endif
                                </div>

                                <div class="mt-4 rounded-lg bg-white p-4 text-sm text-gray-700 ring-1 ring-inset ring-gray-100">
                                    <span class="font-semibold text-gray-900">Recommended next step:</span>
                                    {{ $item['recommended_next_step'] }}
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
