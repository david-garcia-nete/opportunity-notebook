<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-semibold leading-tight text-gray-900">Create Theme</h2>
    </x-slot>

    <div class="bg-gray-50 py-10">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('themes.store') }}" class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                @csrf
                @include('themes.partials.form')
                <div class="mt-6 flex items-center justify-end gap-3">
                    <a href="{{ route('themes.index') }}" class="text-sm font-semibold text-gray-600 hover:text-gray-900">Cancel</a>
                    <button type="submit" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">Create Theme</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
