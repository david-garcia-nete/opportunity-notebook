<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-indigo-600">Relationship Activity</p>
                <h2 class="text-2xl font-semibold leading-tight text-gray-900">Edit Contact Interaction</h2>
            </div>
            <a href="{{ $redirectTo === 'opportunity' && $interaction->opportunity_id ? route('opportunities.show', $interaction->opportunity_id) : route('contacts.show', $interaction->contact_id) }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Back
            </a>
        </div>
    </x-slot>

    <div class="bg-gray-50 py-10">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('contact-interactions.update', $interaction) }}" class="grid gap-5 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm sm:grid-cols-2">
                @method('PATCH')
                @include('contact-interactions.partials.form', ['submitLabel' => 'Update Interaction'])
            </form>
        </div>
    </div>
</x-app-layout>
