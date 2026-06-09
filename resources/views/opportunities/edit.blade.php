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
                        <x-text-input id="status" name="status" type="text" class="mt-1 block w-full" :value="old('status', $opportunity->status)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('status')" />
                    </div>

                    <div>
                        <x-input-label for="score" :value="__('Score')" />
                        <x-text-input id="score" name="score" type="number" min="0" class="mt-1 block w-full" :value="old('score', $opportunity->score)" />
                        <x-input-error class="mt-2" :messages="$errors->get('score')" />
                    </div>
                </div>

                @include('opportunities.partials.evaluation-fields', ['opportunity' => $opportunity])

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
