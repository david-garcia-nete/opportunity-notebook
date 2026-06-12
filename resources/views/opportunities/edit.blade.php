<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium uppercase tracking-wide text-indigo-600">Opportunities</p>
            <h2 class="text-2xl font-semibold leading-tight text-gray-900">
                {{ __('Edit Opportunity') }}
            </h2>
        </div>
    </x-slot>

    <div class="bg-gray-50 py-10">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('opportunities.update', $opportunity) }}" class="space-y-6 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                @csrf
                @method('PATCH')

                <div>
                    <x-input-label for="title" :value="__('Title')" />
                    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $opportunity->title)" required autofocus />
                    <x-input-error class="mt-2" :messages="$errors->get('title')" />
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <x-input-label for="company" :value="__('Company')" />
                        <x-text-input id="company" name="company" type="text" class="mt-1 block w-full" :value="old('company', $opportunity->company)" />
                        <x-input-error class="mt-2" :messages="$errors->get('company')" />
                    </div>

                    <div>
                        <x-input-label for="type" :value="__('Type')" />
                        <x-text-input id="type" name="type" type="text" class="mt-1 block w-full" :value="old('type', $opportunity->type)" />
                        <x-input-error class="mt-2" :messages="$errors->get('type')" />
                    </div>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}" @selected(old('status', $opportunity->status) === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('status')" />
                    </div>

                    <div>
                        <x-input-label for="score" :value="__('Score')" />
                        <x-text-input id="score" name="score" type="number" min="0" class="mt-1 block w-full" :value="old('score', $opportunity->score)" />
                        <x-input-error class="mt-2" :messages="$errors->get('score')" />
                    </div>
                </div>

                <div class="rounded-xl bg-indigo-50 p-4 ring-1 ring-inset ring-indigo-100">
                    <label for="is_focus" class="flex items-start gap-3">
                        <input id="is_focus" name="is_focus" type="checkbox" value="1" @checked(old('is_focus', $opportunity->is_focus)) class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                        <span>
                            <span class="block text-sm font-semibold text-gray-900">Current focus opportunity</span>
                            <span class="mt-1 block text-sm text-gray-600">Mark this when the opportunity deserves active attention right now.</span>
                        </span>
                    </label>
                    <x-input-error class="mt-2" :messages="$errors->get('is_focus')" />

                    <div class="mt-4">
                        <x-input-label for="focus_reason" :value="__('Focus reason')" />
                        <textarea id="focus_reason" name="focus_reason" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('focus_reason', $opportunity->focus_reason) }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('focus_reason')" />
                    </div>
                </div>

                @include('themes.partials.selection')

                @include('opportunities.partials.evaluation-fields', ['opportunity' => $opportunity])

                <section class="rounded-xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-100">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-slate-600">Outcome</p>
                        <p class="mt-1 text-sm text-slate-500">Record what happened so the notebook can learn which efforts produce results.</p>
                    </div>

                    <div class="mt-4 grid gap-6 sm:grid-cols-2">
                        <div>
                            <x-input-label for="outcome" :value="__('Outcome')" />
                            <select id="outcome" name="outcome" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">No outcome yet</option>
                                @foreach ($outcomes as $outcome)
                                    <option value="{{ $outcome }}" @selected(old('outcome', $opportunity->outcome) === $outcome)>{{ $outcome }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('outcome')" />
                        </div>

                        <div>
                            <x-input-label for="outcome_date" :value="__('Outcome date')" />
                            <x-text-input id="outcome_date" name="outcome_date" type="date" class="mt-1 block w-full" :value="old('outcome_date', optional($opportunity->outcome_date)->format('Y-m-d'))" />
                            <x-input-error class="mt-2" :messages="$errors->get('outcome_date')" />
                        </div>

                        <div class="sm:col-span-2">
                            <x-input-label for="outcome_reason" :value="__('Outcome reason')" />
                            <select id="outcome_reason" name="outcome_reason" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select why it happened</option>
                                @foreach ($outcomeReasons as $value => $label)
                                    <option value="{{ $value }}" @selected(old('outcome_reason', $opportunity->outcome_reason) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-slate-500">Use this for Won, Lost, Abandoned, No Response, and Not Pursued outcomes.</p>
                            <x-input-error class="mt-2" :messages="$errors->get('outcome_reason')" />
                        </div>
                    </div>

                    <div class="mt-4">
                        <x-input-label for="outcome_notes" :value="__('Outcome notes')" />
                        <textarea id="outcome_notes" name="outcome_notes" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('outcome_notes', $opportunity->outcome_notes) }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('outcome_notes')" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="lesson_learned" :value="__('Lesson learned')" />
                        <textarea id="lesson_learned" name="lesson_learned" rows="4" placeholder="What should this teach future decisions?" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('lesson_learned', $opportunity->lesson_learned) }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('lesson_learned')" />
                    </div>
                </section>

                <div>
                    <x-input-label for="notes" :value="__('Notes')" />
                    <textarea id="notes" name="notes" rows="5" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $opportunity->notes) }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('opportunities.show', $opportunity) }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</a>
                    <button type="submit" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Update Opportunity
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
