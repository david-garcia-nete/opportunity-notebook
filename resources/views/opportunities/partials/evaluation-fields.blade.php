@php
    $evaluationFields = \App\Models\Opportunity::EVALUATION_FIELDS;
@endphp

<section class="space-y-4 rounded-xl bg-gray-50 p-4 ring-1 ring-inset ring-gray-100">
    <div>
        <h3 class="text-base font-semibold text-gray-900">Evaluation</h3>
        <p class="mt-1 text-sm text-gray-500">Use optional 1–10 ratings to compare opportunities against each other.</p>
    </div>

    <div class="grid gap-6 sm:grid-cols-2">
        @foreach ($evaluationFields as $field => $label)
            <div>
                <x-input-label :for="$field" :value="__($label)" />
                <x-text-input :id="$field" :name="$field" type="number" min="1" max="10" class="mt-1 block w-full" :value="old($field, isset($opportunity) ? $opportunity->{$field} : null)" />
                <p class="mt-1 text-xs text-gray-500">Optional, 1–10.</p>
                <x-input-error class="mt-2" :messages="$errors->get($field)" />
            </div>
        @endforeach
    </div>
</section>
