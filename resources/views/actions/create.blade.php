<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium uppercase tracking-wide text-indigo-600">Actions</p>
            <h2 class="text-2xl font-semibold leading-tight text-gray-900">
                {{ __('New Action') }}
            </h2>
        </div>
    </x-slot>

    <div class="bg-gray-50 py-10">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('actions.store') }}" class="space-y-6 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                @csrf

                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                    <input id="title" name="title" type="text" value="{{ old('title', $defaults['title']) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    @error('title')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                @if ($sourceGap)
                    <div class="rounded-xl bg-indigo-50 p-4 text-sm text-indigo-900 ring-1 ring-inset ring-indigo-100">
                        <p class="font-semibold">Creating an action from gap: {{ $sourceGap->title }}</p>
                        <p class="mt-1">This action will be linked to {{ $sourceGap->opportunity->title }} automatically.</p>
                    </div>
                    <input type="hidden" name="opportunity_id" value="{{ $defaults['opportunity_id'] }}">
                    <input type="hidden" name="opportunity_gap_id" value="{{ $defaults['opportunity_gap_id'] }}">
                @else
                    <div>
                        <label for="opportunity_id" class="block text-sm font-medium text-gray-700">Opportunity</label>
                        <select id="opportunity_id" name="opportunity_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">No related opportunity</option>
                            @foreach ($opportunities as $opportunity)
                                <option value="{{ $opportunity->id }}" @selected((int) old('opportunity_id', $selectedOpportunityId) === $opportunity->id)>{{ $opportunity->title }}</option>
                            @endforeach
                        </select>
                        @error('opportunity_id')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                @error('opportunity_gap_id')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror

                <div>
                    <label for="due_date" class="block text-sm font-medium text-gray-700">Due Date</label>
                    <input id="due_date" name="due_date" type="date" value="{{ old('due_date') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    @error('due_date')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="completed_at" class="block text-sm font-medium text-gray-700">Completed At</label>
                    <input id="completed_at" name="completed_at" type="datetime-local" value="{{ old('completed_at') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    @error('completed_at')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea id="description" name="description" rows="5" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $defaults['description']) }}</textarea>
                    @error('description')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('actions.index') }}" class="text-sm font-semibold text-gray-600 hover:text-gray-900">Cancel</a>
                    <button type="submit" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Create Action
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
