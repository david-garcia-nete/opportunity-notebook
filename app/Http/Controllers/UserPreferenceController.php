<?php

namespace App\Http\Controllers;

use App\Models\UserPreference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserPreferenceController extends Controller
{
    public function edit(Request $request): View
    {
        $preference = $request->user()->preference()
            ->firstOrCreate([], UserPreference::defaults());

        return view('preferences.edit', [
            'preference' => $preference,
            'weightFields' => UserPreference::WEIGHT_FIELDS,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            collect(array_keys(UserPreference::WEIGHT_FIELDS))
                ->mapWithKeys(fn (string $field) => [$field => ['required', 'integer', 'min:0', 'max:10']])
                ->all()
        );

        $request->user()->preference()
            ->updateOrCreate([], $validated);

        return redirect()
            ->route('preferences.edit')
            ->with('status', 'Opportunity weighting preferences updated.');
    }
}
