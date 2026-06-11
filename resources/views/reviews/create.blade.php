<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium uppercase tracking-wide text-indigo-600">Reviews</p>
            <h2 class="text-2xl font-semibold leading-tight text-gray-900">
                {{ __('New Review') }}
            </h2>
        </div>
    </x-slot>

    <div class="bg-gray-50 py-10">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('reviews.store') }}" class="space-y-6 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                @csrf

                <div>
                    <label for="review_type" class="block text-sm font-medium text-gray-700">Review Type</label>
                    <select id="review_type" name="review_type" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select review type</option>
                        @foreach ($reviewTypes as $value => $label)
                            <option value="{{ $value }}" @selected(old('review_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('review_type')" class="mt-2" />
                </div>

                <div>
                    <label for="completed_at" class="block text-sm font-medium text-gray-700">Completed At</label>
                    <input id="completed_at" name="completed_at" type="datetime-local" value="{{ old('completed_at', $defaultCompletedAt) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <p class="mt-2 text-sm text-gray-500">Leave blank to record this review as completed now.</p>
                    <x-input-error :messages="$errors->get('completed_at')" class="mt-2" />
                </div>

                <div>
                    <label for="summary" class="block text-sm font-medium text-gray-700">Summary</label>
                    <textarea id="summary" name="summary" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('summary') }}</textarea>
                    <x-input-error :messages="$errors->get('summary')" class="mt-2" />
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                    <textarea id="notes" name="notes" rows="6" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes') }}</textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('reviews.index') }}" class="text-sm font-semibold text-gray-600 hover:text-gray-900">Cancel</a>
                    <button type="submit" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Record Review
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
