@php
    $selectedThemeIds = collect(old('theme_ids', isset($selectedThemes) ? $selectedThemes->pluck('id')->all() : []))->map(fn ($id) => (int) $id)->all();
@endphp

<section class="rounded-xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-100">
    <div>
        <p class="text-sm font-semibold uppercase tracking-wide text-slate-600">Themes</p>
        <p class="mt-1 text-sm text-slate-500">Group this work into strategic arenas rather than loose tags.</p>
    </div>

    <div class="mt-4 grid gap-3 sm:grid-cols-2">
        @forelse ($availableThemes as $theme)
            <label class="flex items-start gap-3 rounded-lg bg-white p-3 ring-1 ring-inset ring-gray-100">
                <input name="theme_ids[]" type="checkbox" value="{{ $theme->id }}" @checked(in_array($theme->id, $selectedThemeIds, true)) class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                <span>
                    <span class="block text-sm font-semibold text-gray-900">{{ $theme->name }}</span>
                    <span class="mt-1 block text-xs text-gray-500">{{ $theme->active ? 'Active' : 'Inactive' }}{{ $theme->priority !== null ? ' · Priority '.$theme->priority : '' }}</span>
                </span>
            </label>
        @empty
            <p class="rounded-lg bg-white p-3 text-sm text-gray-500 ring-1 ring-inset ring-gray-100 sm:col-span-2">No active themes available yet.</p>
        @endforelse
    </div>

    @error('theme_ids')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
    @error('theme_ids.*')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</section>
