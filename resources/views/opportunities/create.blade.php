<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium uppercase tracking-wide text-indigo-600">Opportunities</p>
            <h2 class="text-2xl font-semibold leading-tight text-gray-900">
                {{ __('New Opportunity') }}
            </h2>
        </div>
    </x-slot>

    <div class="bg-gray-50 py-10">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('opportunities.store') }}" class="space-y-6 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                @csrf

                <div>
                    <x-input-label for="title" :value="__('Title')" />
                    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title')" required autofocus />
                    <x-input-error class="mt-2" :messages="$errors->get('title')" />
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <x-input-label for="company" :value="__('Company')" />
                        <x-text-input id="company" name="company" type="text" class="mt-1 block w-full" :value="old('company')" />
                        <x-input-error class="mt-2" :messages="$errors->get('company')" />
                    </div>

                    <div>
                        <x-input-label for="type" :value="__('Type')" />
                        <x-text-input id="type" name="type" type="text" class="mt-1 block w-full" :value="old('type')" />
                        <x-input-error class="mt-2" :messages="$errors->get('type')" />
                    </div>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}" @selected(old('status', $defaultStatus) === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('status')" />
                    </div>

                    <div>
                        <x-input-label for="score" :value="__('Score')" />
                        <x-text-input id="score" name="score" type="number" min="0" class="mt-1 block w-full" :value="old('score')" />
                        <x-input-error class="mt-2" :messages="$errors->get('score')" />
                    </div>
                </div>

                <div class="rounded-xl bg-indigo-50 p-4 ring-1 ring-inset ring-indigo-100">
                    <label for="is_focus" class="flex items-start gap-3">
                        <input id="is_focus" name="is_focus" type="checkbox" value="1" @checked(old('is_focus')) class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                        <span>
                            <span class="block text-sm font-semibold text-gray-900">Current focus opportunity</span>
                            <span class="mt-1 block text-sm text-gray-600">Mark this when the opportunity deserves active attention right now.</span>
                        </span>
                    </label>
                    <x-input-error class="mt-2" :messages="$errors->get('is_focus')" />

                    <div class="mt-4">
                        <x-input-label for="focus_reason" :value="__('Focus reason')" />
                        <textarea id="focus_reason" name="focus_reason" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('focus_reason') }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('focus_reason')" />
                    </div>
                </div>

                @include('opportunities.partials.evaluation-fields')

                <div>
                    <x-input-label for="notes" :value="__('Notes')" />
                    <textarea id="notes" name="notes" rows="5" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes') }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('opportunities.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</a>
                    <button type="submit" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Create Opportunity
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
