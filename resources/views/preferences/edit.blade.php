<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-indigo-600">Settings</p>
                <h2 class="text-2xl font-semibold leading-tight text-gray-900">
                    {{ __('Opportunity Weighting') }}
                </h2>
                <p class="mt-1 text-sm text-gray-500">Tell Opportunity Notebook which scoring factors matter most to you.</p>
            </div>
        </div>
    </x-slot>

    <div class="bg-gray-50 py-10">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-xl bg-green-50 p-4 text-sm font-medium text-green-700 ring-1 ring-inset ring-green-100">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('preferences.update') }}" class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                @csrf
                @method('PATCH')

                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Personal priorities</h3>
                        <p class="mt-1 text-sm text-gray-500">Use 0 for not important and 10 for extremely important. Defaults are 5 for every factor.</p>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        @foreach ($weightFields as $field => $label)
                            <div>
                                <x-input-label :for="$field" :value="$label" />
                                <x-text-input
                                    :id="$field"
                                    :name="$field"
                                    type="number"
                                    min="0"
                                    max="10"
                                    class="mt-1 block w-full"
                                    :value="old($field, $preference->{$field})"
                                    required
                                />
                                <x-input-error class="mt-2" :messages="$errors->get($field)" />
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-8 flex items-center justify-end gap-3">
                    <a href="{{ route('dashboard') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</a>
                    <x-primary-button>Save Preferences</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
