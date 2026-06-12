<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-indigo-600">Opportunity</p>
                <h2 class="text-2xl font-semibold leading-tight text-gray-900">
                    {{ $opportunity->title }}
                </h2>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('opportunities.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Back
                </a>
                <a href="{{ route('opportunities.edit', $opportunity) }}" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Edit
                </a>
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
                        <dt class="text-sm font-medium text-gray-500">Company</dt>
                        <dd class="mt-1 text-base font-semibold text-gray-900">{{ $opportunity->company ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Type</dt>
                        <dd class="mt-1 text-base font-semibold text-gray-900">{{ $opportunity->type ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1 text-base font-semibold text-gray-900">{{ $opportunity->status }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Manual Score</dt>
                        <dd class="mt-1 text-base font-semibold text-gray-900">{{ $opportunity->score ?? '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Themes</dt>
                        <dd class="mt-2 flex flex-wrap gap-2">
                            @forelse ($opportunity->themes as $theme)
                                <a href="{{ route('themes.show', $theme) }}" class="inline-flex rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-100">{{ $theme->name }}</a>
                            @empty
                                <span class="text-sm text-gray-500">No themes linked yet.</span>
                            @endforelse
                        </dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Notes</dt>
                        <dd class="mt-2 whitespace-pre-line rounded-xl bg-gray-50 p-4 text-sm leading-6 text-gray-700 ring-1 ring-inset ring-gray-100">{{ $opportunity->notes ?: 'No notes yet.' }}</dd>
                    </div>
                </dl>
            </div>


            <section data-testid="opportunity-learning" class="mt-8 rounded-2xl border border-indigo-100 bg-white p-6 shadow-sm">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Learning</p>
                    <h3 class="mt-1 text-lg font-semibold text-gray-900">What should this opportunity teach future decisions?</h3>
                </div>

                <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-100">
                        <dt class="text-sm font-medium text-gray-500">Outcome</dt>
                        <dd class="mt-1 text-base font-semibold text-gray-900">{{ $opportunity->outcome ?? 'No outcome yet' }}</dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-100">
                        <dt class="text-sm font-medium text-gray-500">Outcome Reason</dt>
                        <dd class="mt-1 text-base font-semibold text-gray-900">{{ $opportunity->outcomeReasonLabel() ?? 'No reason recorded' }}</dd>
                    </div>
                    <div class="sm:col-span-2 rounded-xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-100">
                        <dt class="text-sm font-medium text-gray-500">Lesson Learned</dt>
                        <dd class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-700">{{ $opportunity->lesson_learned ?: 'No lesson recorded yet.' }}</dd>
                    </div>
                </dl>
            </section>


            <section class="mt-8 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Decision Log</p>
                        <h3 class="mt-1 text-lg font-semibold text-gray-900">Why did my priority change?</h3>
                        <p class="mt-1 text-sm text-gray-500">Record why this opportunity was focused, continued, intensified, parked, abandoned, or reopened.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('opportunities.decisions.store', $opportunity) }}" class="mt-6 rounded-xl bg-slate-50 p-5 ring-1 ring-inset ring-slate-100">
                    @csrf

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="decision_type" class="block text-sm font-medium text-gray-700">Decision</label>
                            <select id="decision_type" name="decision_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select decision</option>
                                @foreach ($decisionTypes as $value => $label)
                                    <option value="{{ $value }}" @selected(old('decision_type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('decision_type')" class="mt-2" />
                        </div>

                        <div>
                            <label for="reason_category" class="block text-sm font-medium text-gray-700">Primary reason</label>
                            <select id="reason_category" name="reason_category" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select reason</option>
                                @foreach ($reasonCategories as $value => $label)
                                    <option value="{{ $value }}" @selected(old('reason_category') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('reason_category')" class="mt-2" />
                        </div>

                        <div>
                            <label for="decided_at" class="block text-sm font-medium text-gray-700">Decision date</label>
                            <input id="decided_at" name="decided_at" type="datetime-local" value="{{ old('decided_at', now()->format('Y-m-d\TH:i')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <x-input-error :messages="$errors->get('decided_at')" class="mt-2" />
                        </div>

                        <div class="sm:col-span-2">
                            <label for="decision_notes" class="block text-sm font-medium text-gray-700">Notes</label>
                            <textarea id="decision_notes" name="notes" rows="3" placeholder="Capture the strategic context: tradeoffs, constraints, alternatives, or new evidence." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes') }}</textarea>
                            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end">
                        <button type="submit" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            Record Decision
                        </button>
                    </div>
                </form>

                @if ($opportunity->decisions->isEmpty())
                    <div class="mt-5 rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">
                        No decisions logged yet. Add the first entry when you focus, continue, intensify, park, abandon, or reopen this opportunity.
                    </div>
                @else
                    <ol class="mt-6 space-y-4">
                        @foreach ($opportunity->decisions as $decision)
                            <li class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-100">{{ $decision->decisionTypeLabel() }}</span>
                                            <span class="rounded-full bg-gray-50 px-2.5 py-1 text-xs font-semibold text-gray-600 ring-1 ring-inset ring-gray-200">{{ $decision->reasonCategoryLabel() }}</span>
                                        </div>
                                        @if ($decision->notes)
                                            <p class="mt-3 whitespace-pre-line text-sm leading-6 text-gray-700">{{ $decision->notes }}</p>
                                        @else
                                            <p class="mt-3 text-sm text-gray-500">No notes recorded.</p>
                                        @endif
                                    </div>
                                    <time datetime="{{ $decision->decided_at->toDateTimeString() }}" class="text-sm font-medium text-gray-500">{{ $decision->decided_at->toFormattedDateString() }}</time>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>

            <section class="mt-8 rounded-2xl border border-indigo-100 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Portfolio Readiness</p>
                        <h3 class="mt-1 text-lg font-semibold text-gray-900">Am I prepared to win this opportunity?</h3>
                        <p class="mt-1 text-sm text-gray-500">Readiness is calculated from evidence and gaps, not from whether the opportunity is worth pursuing.</p>
                    </div>
                    <div class="rounded-xl bg-indigo-50 px-5 py-4 text-right ring-1 ring-inset ring-indigo-100">
                        <p class="text-sm font-medium text-indigo-700">Readiness Score</p>
                        <p class="mt-1 text-3xl font-bold text-indigo-950">{{ $readinessIndicators['score'] }}</p>
                        <span class="mt-2 inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-200">{{ $readinessIndicators['status'] }}</span>
                    </div>
                </div>

                <dl class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <div class="rounded-xl bg-gray-50 p-4 ring-1 ring-inset ring-gray-100">
                        <dt class="text-sm font-medium text-gray-500">Linked Projects</dt>
                        <dd class="mt-1 text-2xl font-bold text-gray-900">{{ $readinessIndicators['projects_count'] }}</dd>
                    </div>
                    <div class="rounded-xl bg-gray-50 p-4 ring-1 ring-inset ring-gray-100">
                        <dt class="text-sm font-medium text-gray-500">Open Gaps</dt>
                        <dd class="mt-1 text-2xl font-bold text-gray-900">{{ $readinessIndicators['open_gaps_count'] }}</dd>
                    </div>
                    <div class="rounded-xl bg-gray-50 p-4 ring-1 ring-inset ring-gray-100">
                        <dt class="text-sm font-medium text-gray-500">Completed Gaps</dt>
                        <dd class="mt-1 text-2xl font-bold text-gray-900">{{ $readinessIndicators['completed_gaps_count'] }}</dd>
                    </div>
                    <div class="rounded-xl bg-gray-50 p-4 ring-1 ring-inset ring-gray-100">
                        <dt class="text-sm font-medium text-gray-500">Related Applications</dt>
                        <dd class="mt-1 text-2xl font-bold text-gray-900">{{ $readinessIndicators['applications_count'] }}</dd>
                    </div>
                    <div class="rounded-xl bg-gray-50 p-4 ring-1 ring-inset ring-gray-100">
                        <dt class="text-sm font-medium text-gray-500">Strategic Objectives</dt>
                        <dd class="mt-1 text-2xl font-bold text-gray-900">{{ $readinessIndicators['strategic_objectives_count'] }}</dd>
                    </div>
                </dl>

                <div class="mt-6 grid gap-6 lg:grid-cols-2">
                    <div class="rounded-xl bg-slate-50 p-5 ring-1 ring-inset ring-slate-100">
                        <h4 class="text-base font-semibold text-gray-900">Readiness Breakdown</h4>
                        <dl class="mt-4 space-y-3">
                            @foreach ($readinessBreakdown as $item)
                                <div class="flex items-center justify-between text-sm">
                                    <dt class="text-gray-600">{{ $item['label'] }}{{ $item['count'] !== null ? ' ('.$item['count'].')' : '' }}</dt>
                                    <dd class="font-semibold {{ $item['points'] < 0 ? 'text-red-700' : 'text-green-700' }}">{{ $item['points'] > 0 ? '+' : '' }}{{ $item['points'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-5 ring-1 ring-inset ring-slate-100">
                        <h4 class="text-base font-semibold text-gray-900">Evidence Summary</h4>
                        <div class="mt-4 space-y-4 text-sm text-gray-600">
                            <div>
                                <p class="font-semibold text-gray-900">Linked Projects</p>
                                <p>{{ $opportunity->projects->pluck('name')->join(', ') ?: 'No projects linked yet.' }}</p>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900">Open Gaps</p>
                                <p>{{ $opportunity->opportunityGaps->where('status', \App\Support\Statuses::GAP_OPEN)->pluck('title')->join(', ') ?: 'No open gaps.' }}</p>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900">Completed Gaps</p>
                                <p>{{ $opportunity->opportunityGaps->where('status', \App\Support\Statuses::GAP_COMPLETE)->pluck('title')->join(', ') ?: 'No completed gaps yet.' }}</p>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900">Related Applications</p>
                                <p>{{ $opportunity->applications->pluck('status')->join(', ') ?: 'No applications yet.' }}</p>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900">Strategic Objectives</p>
                                <p>{{ $opportunity->strategicObjectives->pluck('name')->join(', ') ?: 'No strategic objectives linked yet.' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>


            <section data-testid="opportunity-forecast" class="mt-8 rounded-2xl border border-indigo-100 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Forecast</p>
                        <h3 class="mt-1 text-lg font-semibold text-gray-900">How likely is this opportunity to succeed?</h3>
                        <p class="mt-1 text-sm text-gray-500">Forecasting is rules-based: 40% weighted score, 40% readiness, 20% execution health.</p>
                    </div>
                    <div class="rounded-xl bg-indigo-50 px-5 py-4 text-right ring-1 ring-inset ring-indigo-100">
                        <p class="text-sm font-medium text-indigo-700">Forecast Score</p>
                        <p class="mt-1 text-3xl font-bold text-indigo-950">{{ $forecastScore }}</p>
                        <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-indigo-700">Forecast Status</p>
                        <span class="mt-1 inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-200">{{ $forecastStatus }}</span>
                    </div>
                </div>

                <div class="mt-6 rounded-xl bg-slate-50 p-5 ring-1 ring-inset ring-slate-100">
                    <h4 class="text-base font-semibold text-gray-900">Forecast Breakdown</h4>
                    <dl class="mt-4 space-y-3">
                        @foreach ($forecastBreakdown as $item)
                            <div class="flex items-center justify-between text-sm">
                                <dt class="text-gray-600">{{ $item['label'] }}:</dt>
                                <dd class="font-semibold {{ $item['label'] === 'Forecast' ? 'text-indigo-700' : 'text-green-700' }}">{{ $item['label'] === 'Forecast' ? '' : '+' }}{{ $item['points'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </section>

            @php($nextAction = $opportunity->nextAction())

            <section class="mt-8 rounded-2xl border border-indigo-100 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Next Action</p>
                        <h3 class="mt-1 text-lg font-semibold text-gray-900">Keep this opportunity moving</h3>
                        <p class="mt-1 text-sm text-gray-500">Every active opportunity should have a clear next step or be intentionally parked.</p>
                    </div>
                    <a href="{{ route('actions.create', ['opportunity_id' => $opportunity->id]) }}" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Create Action
                    </a>
                </div>

                @if ($nextAction)
                    <div class="mt-5 rounded-xl bg-indigo-50 p-5 ring-1 ring-inset ring-indigo-100">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <a href="{{ route('actions.show', $nextAction) }}" class="text-lg font-semibold text-indigo-700 hover:text-indigo-900">{{ $nextAction->title }}</a>
                                <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                                    <div>
                                        <dt class="font-medium text-indigo-900">Due date</dt>
                                        <dd class="mt-1 text-indigo-800">{{ $nextAction->due_date?->toFormattedDateString() ?? 'No due date' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-medium text-indigo-900">Status</dt>
                                        <dd class="mt-1 text-indigo-800">{{ $nextAction->status() }}</dd>
                                    </div>
                                </dl>
                            </div>
                            <a href="{{ route('actions.edit', $nextAction) }}" class="text-sm font-semibold text-indigo-700 hover:text-indigo-900">Edit action</a>
                        </div>
                    </div>
                @elseif ($opportunity->missingNextAction())
                    <div class="mt-5 rounded-xl bg-amber-50 p-5 text-sm text-amber-900 ring-1 ring-inset ring-amber-200">
                        <p class="font-semibold">Missing next action</p>
                        <p class="mt-1">This opportunity is open but has no incomplete action. Create a concrete next step, or park/archive the opportunity if you are intentionally not pursuing it.</p>
                    </div>
                @else
                    <div class="mt-5 rounded-xl bg-gray-50 p-5 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">
                        No next action is required while this opportunity is parked, archived, closed, or rejected.
                    </div>
                @endif
            </section>

            <section class="mt-8 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Evaluation</h3>
                        <p class="mt-1 text-sm text-gray-500">The computed score is a decision aid for comparing opportunities, not an absolute truth.</p>
                    </div>
                    <div class="rounded-xl bg-indigo-50 px-4 py-3 text-center ring-1 ring-inset ring-indigo-100">
                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Computed Score</p>
                        <p class="mt-1 text-2xl font-bold text-indigo-900">{{ $opportunity->computedScore() ?? '—' }}</p>
                    </div>
                </div>

                <dl class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach (\App\Models\Opportunity::EVALUATION_FIELDS as $field => $label)
                        <div class="rounded-xl bg-gray-50 p-4 ring-1 ring-inset ring-gray-100">
                            <dt class="text-sm font-medium text-gray-500">{{ $label }}</dt>
                            <dd class="mt-1 text-lg font-semibold text-gray-900">{{ $opportunity->{$field} ?? '—' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>



            <section class="mt-8 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Gap Analysis</p>
                        <h3 class="mt-1 text-lg font-semibold text-gray-900">What stands between me and this opportunity?</h3>
                        <p class="mt-1 text-sm text-gray-500">Manually track skills, experience, credentials, portfolio work, and other missing pieces.</p>
                    </div>
                    <a href="{{ route('opportunities.gaps.create', $opportunity) }}" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Add Gap
                    </a>
                </div>

                <dl class="mt-6 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-xl bg-red-50 p-4 ring-1 ring-inset ring-red-100">
                        <dt class="text-sm font-medium text-red-700">Open</dt>
                        <dd class="mt-1 text-3xl font-bold text-red-900">{{ $gapCounts['Open'] }}</dd>
                    </div>
                    <div class="rounded-xl bg-amber-50 p-4 ring-1 ring-inset ring-amber-100">
                        <dt class="text-sm font-medium text-amber-700">In Progress</dt>
                        <dd class="mt-1 text-3xl font-bold text-amber-900">{{ $gapCounts['In Progress'] }}</dd>
                    </div>
                    <div class="rounded-xl bg-green-50 p-4 ring-1 ring-inset ring-green-100">
                        <dt class="text-sm font-medium text-green-700">Completed</dt>
                        <dd class="mt-1 text-3xl font-bold text-green-900">{{ $gapCounts['Complete'] }}</dd>
                    </div>
                </dl>

                <section class="mt-6 rounded-xl bg-slate-50 p-5 ring-1 ring-inset ring-slate-100">
                    <h4 class="text-base font-semibold text-gray-900">Gap Progress</h4>
                    <dl class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Critical Gaps</dt>
                            <dd class="mt-1 text-2xl font-bold text-gray-900">{{ $gapPriorityCounts['Critical'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">High Gaps</dt>
                            <dd class="mt-1 text-2xl font-bold text-gray-900">{{ $gapPriorityCounts['High'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Gap Actions Open</dt>
                            <dd class="mt-1 text-2xl font-bold text-gray-900">{{ $gapActionOpenCount }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Gap Actions Completed</dt>
                            <dd class="mt-1 text-2xl font-bold text-gray-900">{{ $gapActionCompletedCount }}</dd>
                        </div>
                    </dl>
                </section>

                @if ($opportunity->opportunityGaps->isEmpty())
                    <div class="mt-5 rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">
                        No gaps recorded yet. Add the manual blockers or investments needed before this opportunity is realistic.
                    </div>
                @else
                    <div class="mt-6 space-y-6">
                        @foreach ($gapStatuses as $status)
                            @php($gapsForStatus = $opportunity->opportunityGaps->where('status', $status))
                            <div>
                                <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-500">{{ $status }} gaps ({{ $gapsForStatus->count() }})</h4>
                                @if ($gapsForStatus->isEmpty())
                                    <p class="mt-2 rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">No {{ strtolower($status) }} gaps.</p>
                                @else
                                    <div class="mt-3 space-y-3">
                                        @foreach ($gapsForStatus as $gap)
                                            <article class="rounded-xl bg-gray-50 p-4 ring-1 ring-inset ring-gray-100">
                                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                    <div>
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <a href="{{ route('opportunities.gaps.show', [$opportunity, $gap]) }}" class="font-semibold text-indigo-700 hover:text-indigo-900">{{ $gap->title }}</a>
                                                            <span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-gray-600 ring-1 ring-inset ring-gray-200">{{ $gap->category }}</span>
                                                            <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-100">{{ $gap->priority }}</span>
                                                        </div>
                                                        @if ($gap->description)
                                                            <p class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-600">{{ $gap->description }}</p>
                                                        @endif
                                                        <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                                                            <div>
                                                                <dt class="font-medium text-gray-500">Open Actions Linked To This Gap</dt>
                                                                <dd class="mt-1 font-semibold text-gray-900">{{ $gap->actions->whereNull('completed_at')->count() }}</dd>
                                                            </div>
                                                            <div>
                                                                <dt class="font-medium text-gray-500">Completed Actions Linked To This Gap</dt>
                                                                <dd class="mt-1 font-semibold text-gray-900">{{ $gap->actions->whereNotNull('completed_at')->count() }}</dd>
                                                            </div>
                                                        </dl>
                                                    </div>
                                                    <div class="flex items-center gap-3 text-sm">
                                                        <a href="{{ route('actions.create', ['opportunity_gap_id' => $gap->id]) }}" class="font-semibold text-indigo-600 hover:text-indigo-900">Create Action</a>
                                                        <a href="{{ route('opportunities.gaps.edit', [$opportunity, $gap]) }}" class="font-semibold text-gray-600 hover:text-gray-900">Edit</a>
                                                        <form method="POST" action="{{ route('opportunities.gaps.destroy', [$opportunity, $gap]) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="font-semibold text-red-600 hover:text-red-900">Delete</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </article>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
            <section class="mt-8 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Timeline</h3>
                        <p class="mt-1 text-sm text-gray-500">A chronological view of what has happened, what is coming next, and where progress may be stalled.</p>
                    </div>
                    <a href="{{ route('timeline.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        View Global Timeline
                    </a>
                </div>

                <div class="mt-6 grid gap-6 lg:grid-cols-2">
                    <div>
                        <h4 class="text-base font-semibold text-gray-900">Upcoming</h4>
                        @include('timeline.partials.items', ['items' => $timeline['upcoming'], 'emptyMessage' => 'No upcoming timeline items yet.'])
                    </div>
                    <div>
                        <h4 class="text-base font-semibold text-gray-900">Recent History</h4>
                        @include('timeline.partials.items', ['items' => $timeline['history'], 'emptyMessage' => 'No historical timeline items yet.'])
                    </div>
                </div>
            </section>

            <section class="mt-8 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Strategic Objectives</h3>
                        <p class="mt-1 text-sm text-gray-500">Outcomes this opportunity may help you achieve.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('opportunities.strategic-objectives.store', $opportunity) }}" class="mt-5 grid gap-4 rounded-xl bg-gray-50 p-4 ring-1 ring-inset ring-gray-100 sm:grid-cols-2">
                    @csrf
                    <div>
                        <label for="strategic_objective_id" class="block text-sm font-medium text-gray-700">Objective</label>
                        <select id="strategic_objective_id" name="strategic_objective_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Choose an objective</option>
                            @foreach ($availableStrategicObjectives as $strategicObjective)
                                <option value="{{ $strategicObjective->id }}" @selected(old('strategic_objective_id') == $strategicObjective->id)>{{ $strategicObjective->name }} — Priority {{ $strategicObjective->priority }}{{ $strategicObjective->active ? '' : ' (inactive)' }}</option>
                            @endforeach
                        </select>
                        @error('strategic_objective_id')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            Attach Objective
                        </button>
                    </div>
                </form>

                @if ($opportunity->strategicObjectives->isEmpty())
                    <div class="mt-5 rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">
                        No strategic objectives linked to this opportunity yet.
                    </div>
                @else
                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Name</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Priority</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($opportunity->strategicObjectives as $strategicObjective)
                                    <tr>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm font-semibold text-gray-900">
                                            <a href="{{ route('strategic-objectives.show', $strategicObjective) }}" class="text-indigo-600 hover:text-indigo-900">{{ $strategicObjective->name }}</a>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $strategicObjective->priority }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $strategicObjective->active ? 'Active' : 'Inactive' }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-right text-sm">
                                            <form method="POST" action="{{ route('opportunities.strategic-objectives.destroy', [$opportunity, $strategicObjective]) }}">
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

            <section class="mt-8 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Contacts</h3>
                        <p class="mt-1 text-sm text-gray-500">People connected to this opportunity.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('opportunities.contacts.store', $opportunity) }}" class="mt-5 grid gap-4 rounded-xl bg-gray-50 p-4 ring-1 ring-inset ring-gray-100 sm:grid-cols-2">
                    @csrf
                    <div>
                        <label for="contact_id" class="block text-sm font-medium text-gray-700">Contact</label>
                        <select id="contact_id" name="contact_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Choose a contact</option>
                            @foreach ($availableContacts as $contact)
                                <option value="{{ $contact->id }}" @selected(old('contact_id') == $contact->id)>{{ $contact->name }}{{ $contact->organization ? ' — '.$contact->organization : '' }}</option>
                            @endforeach
                        </select>
                        @error('contact_id')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="relationship_type" class="block text-sm font-medium text-gray-700">Relationship Type</label>
                        <input id="relationship_type" name="relationship_type" type="text" value="{{ old('relationship_type') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Recruiter, referral, hiring manager">
                        @error('relationship_type')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="contact_notes" class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea id="contact_notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Context for this relationship">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            Attach Contact
                        </button>
                    </div>
                </form>

                @if ($opportunity->contacts->isEmpty())
                    <div class="mt-5 rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">
                        No contacts linked to this opportunity yet.
                    </div>
                @else
                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Name</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Organization</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Email</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Relationship Type</th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($opportunity->contacts as $contact)
                                    <tr>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm font-semibold text-gray-900">
                                            <a href="{{ route('contacts.show', $contact) }}" class="text-indigo-600 hover:text-indigo-900">{{ $contact->name }}</a>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $contact->organization ?? '—' }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $contact->email ?? '—' }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $contact->pivot->relationship_type ?? '—' }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-right text-sm">
                                            <form method="POST" action="{{ route('opportunities.contacts.destroy', [$opportunity, $contact]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="font-semibold text-red-600 hover:text-red-900">Detach</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>


            <section class="mt-8 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Related Contact Activity</h3>
                        <p class="mt-1 text-sm text-gray-500">Interactions connected to this opportunity, newest first.</p>
                    </div>
                    <a href="{{ route('contact-interactions.create', ['opportunity_id' => $opportunity->id, 'redirect_to' => 'opportunity']) }}" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Add Interaction
                    </a>
                </div>

                @if ($opportunity->contactInteractions->isEmpty())
                    <div class="mt-5 rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">
                        No contact interactions linked to this opportunity yet.
                    </div>
                @else
                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Date</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Contact</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Type</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Summary</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Outcome</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Follow-up</th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($opportunity->contactInteractions as $interaction)
                                    <tr>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm font-semibold text-gray-900">{{ $interaction->interaction_date->toFormattedDateString() }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">
                                            <a href="{{ route('contacts.show', $interaction->contact) }}" class="font-semibold text-indigo-600 hover:text-indigo-900">{{ $interaction->contact->name }}</a>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $interaction->interaction_type }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-600">{{ $interaction->summary }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-600">{{ $interaction->outcome ?? '—' }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $interaction->next_follow_up_date?->toFormattedDateString() ?? '—' }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-right text-sm">
                                            <div class="flex justify-end gap-3">
                                                <a href="{{ route('contact-interactions.edit', ['contact_interaction' => $interaction, 'redirect_to' => 'opportunity']) }}" class="font-semibold text-indigo-600 hover:text-indigo-900">Edit</a>
                                                <form method="POST" action="{{ route('contact-interactions.destroy', $interaction) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="redirect_to" value="opportunity">
                                                    <button type="submit" class="font-semibold text-red-600 hover:text-red-900">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="mt-8 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Projects</h3>
                    <p class="mt-1 text-sm text-gray-500">Portfolio projects supporting this opportunity.</p>
                </div>

                <form method="POST" action="{{ route('opportunities.projects.store', $opportunity) }}" class="mt-5 grid gap-4 rounded-xl bg-gray-50 p-4 ring-1 ring-inset ring-gray-100 sm:grid-cols-2">
                    @csrf
                    <div>
                        <label for="project_id" class="block text-sm font-medium text-gray-700">Project</label>
                        <select id="project_id" name="project_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Choose a project</option>
                            @foreach ($availableProjects as $project)
                                <option value="{{ $project->id }}" @selected(old('project_id') == $project->id)>{{ $project->name }}{{ $project->status ? ' — '.$project->status : '' }}</option>
                            @endforeach
                        </select>
                        @error('project_id')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="project_notes" class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea id="project_notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="How this project supports the opportunity">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            Attach Project
                        </button>
                    </div>
                </form>

                @if ($opportunity->projects->isEmpty())
                    <div class="mt-5 rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">
                        No projects linked to this opportunity yet.
                    </div>
                @else
                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Name</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">URL</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Notes</th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($opportunity->projects as $project)
                                    <tr>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm font-semibold text-gray-900">
                                            <a href="{{ route('projects.show', $project) }}" class="text-indigo-600 hover:text-indigo-900">{{ $project->name }}</a>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $project->status }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">
                                            @if ($project->url)
                                                <a href="{{ $project->url }}" class="text-indigo-600 hover:text-indigo-900" target="_blank" rel="noopener noreferrer">{{ $project->url }}</a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 text-sm text-gray-600">{{ $project->pivot->notes ?? '—' }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-right text-sm">
                                            <form method="POST" action="{{ route('opportunities.projects.destroy', [$opportunity, $project]) }}">
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

            <section class="mt-8 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Applications</h3>
                        <p class="mt-1 text-sm text-gray-500">Actual submissions made toward this opportunity.</p>
                    </div>
                    <a href="{{ route('applications.create', ['opportunity_id' => $opportunity->id]) }}" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Add Application
                    </a>
                </div>

                @if ($opportunity->applications->isEmpty())
                    <div class="mt-5 rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">
                        No applications yet for this opportunity.
                    </div>
                @else
                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Applied Date</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Source</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($opportunity->applications as $application)
                                    <tr>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm font-semibold text-gray-900">
                                            <a href="{{ route('applications.show', $application) }}" class="text-indigo-600 hover:text-indigo-900">{{ $application->applied_at->toFormattedDateString() }}</a>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $application->status }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $application->source ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="mt-8 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Related Actions</h3>
                        <p class="mt-1 text-sm text-gray-500">Tasks that move this opportunity forward.</p>
                    </div>
                    <a href="{{ route('actions.create', ['opportunity_id' => $opportunity->id]) }}" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Add Action
                    </a>
                </div>

                @if ($opportunity->actions->isEmpty())
                    <div class="mt-5 rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-inset ring-gray-100">
                        No actions yet for this opportunity.
                    </div>
                @else
                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Action title</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Due date</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($opportunity->actions as $action)
                                    <tr>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm font-semibold text-gray-900">
                                            <a href="{{ route('actions.show', $action) }}" class="text-indigo-600 hover:text-indigo-900">{{ $action->title }}</a>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $action->due_date?->toFormattedDateString() ?? '—' }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ $action->status() }}</td>
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
