<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium uppercase tracking-wide text-indigo-600">Opportunity Gap</p>
            <h2 class="text-2xl font-semibold leading-tight text-gray-900">Add gap for {{ $opportunity->title }}</h2>
        </div>
    </x-slot>

    <div class="bg-gray-50 py-10">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('opportunities.gaps.store', $opportunity) }}" class="space-y-6 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                @include('opportunity-gaps.partials.form', ['gap' => new \App\Models\OpportunityGap(), 'submitLabel' => 'Create Gap'])
            </form>
        </div>
    </div>
</x-app-layout>
