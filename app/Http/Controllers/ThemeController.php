<?php

namespace App\Http\Controllers;

use App\Models\Theme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ThemeController extends Controller
{
    public function index(): View
    {
        $themes = Theme::query()
            ->withCount(['opportunities', 'projects', 'strategicObjectives'])
            ->with('opportunities')
            ->orderByDesc('active')
            ->orderByRaw('priority is null')
            ->orderBy('priority')
            ->orderBy('name')
            ->get();

        return view('themes.index', [
            'themes' => $themes,
            'themePortfolio' => $this->portfolioAnalysis($themes),
        ]);
    }

    public function create(): View
    {
        return view('themes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $theme = Theme::create($this->validatedTheme($request));

        return redirect()
            ->route('themes.show', $theme)
            ->with('status', 'Theme created.');
    }

    public function show(Theme $theme): View
    {
        $theme->load([
            'opportunities' => fn ($query) => $query->orderByDesc('is_focus')->orderBy('title'),
            'projects' => fn ($query) => $query->orderBy('name'),
            'strategicObjectives' => fn ($query) => $query->orderByDesc('active')->orderByDesc('priority')->orderBy('name'),
        ]);

        return view('themes.show', [
            'theme' => $theme,
            'portfolio' => $this->portfolioAnalysis(collect([$theme]))->first(),
        ]);
    }

    public function edit(Theme $theme): View
    {
        return view('themes.edit', [
            'theme' => $theme,
        ]);
    }

    public function update(Request $request, Theme $theme): RedirectResponse
    {
        $theme->update($this->validatedTheme($request));

        return redirect()
            ->route('themes.show', $theme)
            ->with('status', 'Theme updated.');
    }

    public function destroy(Theme $theme): RedirectResponse
    {
        $theme->delete();

        return redirect()
            ->route('themes.index')
            ->with('status', 'Theme deleted.');
    }

    private function validatedTheme(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['nullable', 'integer'],
            'active' => ['nullable', 'boolean'],
        ]) + ['active' => false];
    }

    private function portfolioAnalysis($themes)
    {
        return $themes->map(function (Theme $theme) {
            $opportunities = $theme->opportunities;
            $scoreValues = $opportunities
                ->map(fn ($opportunity) => $opportunity->computedScore())
                ->filter(fn ($score) => $score !== null);

            return [
                'theme' => $theme,
                'opportunity_count' => $opportunities->count(),
                'focus_opportunity_count' => $opportunities->where('is_focus', true)->count(),
                'won_count' => $opportunities->where('outcome', 'Won')->count(),
                'lost_count' => $opportunities->where('outcome', 'Lost')->count(),
                'abandoned_count' => $opportunities->where('outcome', 'Abandoned')->count(),
                'average_score' => $scoreValues->isEmpty() ? null : round($scoreValues->avg(), 1),
            ];
        });
    }
}
