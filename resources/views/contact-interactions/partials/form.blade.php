@csrf
<input type="hidden" name="redirect_to" value="{{ $redirectTo }}">

<div>
    <label for="contact_id" class="block text-sm font-medium text-gray-700">Contact</label>
    <select id="contact_id" name="contact_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <option value="">Choose a contact</option>
        @foreach ($contacts as $contact)
            <option value="{{ $contact->id }}" @selected(old('contact_id', $interaction->contact_id) == $contact->id)>{{ $contact->name }}</option>
        @endforeach
    </select>
    @error('contact_id')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div>
    <label for="opportunity_id" class="block text-sm font-medium text-gray-700">Opportunity</label>
    <select id="opportunity_id" name="opportunity_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <option value="">No linked opportunity</option>
        @foreach ($opportunities as $opportunity)
            <option value="{{ $opportunity->id }}" @selected(old('opportunity_id', $interaction->opportunity_id) == $opportunity->id)>{{ $opportunity->title }}{{ $opportunity->company ? ' — '.$opportunity->company : '' }}</option>
        @endforeach
    </select>
    @error('opportunity_id')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div>
    <label for="interaction_date" class="block text-sm font-medium text-gray-700">Interaction Date</label>
    <input id="interaction_date" name="interaction_date" type="date" required value="{{ old('interaction_date', $interaction->interaction_date?->format('Y-m-d')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    @error('interaction_date')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div>
    <label for="interaction_type" class="block text-sm font-medium text-gray-700">Interaction Type</label>
    <select id="interaction_type" name="interaction_type" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <option value="">Choose a type</option>
        @foreach ($interactionTypes as $interactionType)
            <option value="{{ $interactionType }}" @selected(old('interaction_type', $interaction->interaction_type) === $interactionType)>{{ $interactionType }}</option>
        @endforeach
    </select>
    @error('interaction_type')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div class="sm:col-span-2">
    <label for="summary" class="block text-sm font-medium text-gray-700">Summary</label>
    <textarea id="summary" name="summary" rows="4" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="What was discussed?">{{ old('summary', $interaction->summary) }}</textarea>
    @error('summary')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div class="sm:col-span-2">
    <label for="outcome" class="block text-sm font-medium text-gray-700">Outcome</label>
    <textarea id="outcome" name="outcome" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="What changed or what did they offer to help with?">{{ old('outcome', $interaction->outcome) }}</textarea>
    @error('outcome')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div>
    <label for="next_follow_up_date" class="block text-sm font-medium text-gray-700">Next Follow-Up Date</label>
    <input id="next_follow_up_date" name="next_follow_up_date" type="date" value="{{ old('next_follow_up_date', $interaction->next_follow_up_date?->format('Y-m-d')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    @error('next_follow_up_date')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div class="sm:col-span-2">
    <button type="submit" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
        {{ $submitLabel }}
    </button>
</div>
