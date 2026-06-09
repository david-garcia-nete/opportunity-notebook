<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-indigo-600">Projects</p>
                <h2 class="text-2xl font-semibold leading-tight text-gray-900">
                    {{ $project->name }}
                </h2>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('projects.edit', $project) }}" class="inline-flex items-center justify-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 transition hover:bg-gray-50">
                    Edit
                </a>
                <a href="{{ route('projects.index') }}" class="text-sm font-semibold text-gray-600 hover:text-gray-900">Back to projects</a>
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
                        <dt class="text-sm font-medium text-gray-500">Name</dt>
                        <dd class="mt-1 text-base font-semibold text-gray-900">{{ $project->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1 text-base font-semibold text-gray-900">{{ $project->status }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">URL</dt>
                        <dd class="mt-1 text-base font-semibold text-gray-900">
                            @if ($project->url)
                                <a href="{{ $project->url }}" class="text-indigo-600 hover:text-indigo-900" target="_blank" rel="noopener noreferrer">{{ $project->url }}</a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Description</dt>
                        <dd class="mt-2 whitespace-pre-line rounded-xl bg-gray-50 p-4 text-sm leading-6 text-gray-700 ring-1 ring-inset ring-gray-100">{{ $project->description ?: 'No description yet.' }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</x-app-layout>
