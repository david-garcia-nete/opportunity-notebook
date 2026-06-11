@csrf

<div>
    <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
    <input id="title" name="title" type="text" value="{{ old('title', $gap->title ?? '') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="AWS certification, stronger portfolio, first client">
    @error('title')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div>
    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
    <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Describe what is missing and why it matters for this opportunity.">{{ old('description', $gap->description ?? '') }}</textarea>
    @error('description')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div class="grid gap-4 sm:grid-cols-3">
    <div>
        <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
        <select id="category" name="category" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @foreach ($categories as $category)
                <option value="{{ $category }}" @selected(old('category', $gap->category ?? 'Other') === $category)>{{ $category }}</option>
            @endforeach
        </select>
        @error('category')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
        <select id="status" name="status" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @foreach ($statuses as $status)
                <option value="{{ $status }}" @selected(old('status', $gap->status ?? 'Open') === $status)>{{ $status }}</option>
            @endforeach
        </select>
        @error('status')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="priority" class="block text-sm font-medium text-gray-700">Priority</label>
        <select id="priority" name="priority" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @foreach ($priorities as $priority)
                <option value="{{ $priority }}" @selected(old('priority', $gap->priority ?? 'Medium') === $priority)>{{ $priority }}</option>
            @endforeach
        </select>
        @error('priority')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="flex items-center justify-end gap-3">
    <a href="{{ route('opportunities.show', $opportunity) }}" class="text-sm font-semibold text-gray-600 hover:text-gray-900">Cancel</a>
    <button type="submit" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
        {{ $submitLabel }}
    </button>
</div>
